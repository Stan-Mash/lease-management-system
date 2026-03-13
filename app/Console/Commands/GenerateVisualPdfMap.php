<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LeaseTemplate;
use App\Services\DefaultLeasePdfCoordinateMap;
use App\Services\PdfOverlayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Render a visual coordinate map for a real lease template PDF.
 *
 * Stamps brightly coloured boxes at every coordinate in the template's
 * pdf_coordinate_map so you can visually verify alignment against the
 * actual document. Text-field slots get a red label; signature slots get
 * blue (tenant), red (manager/lessor), green (witness), orange (advocate),
 * and purple (guarantor) filled rectangles.
 *
 * Usage:
 *   php artisan lease:visual-map {template_id}
 *
 * Output:
 *   template-{id}-mapped.pdf   (written to the project root)
 */
class GenerateVisualPdfMap extends Command
{
    protected $signature = 'lease:visual-map
        {template_id : Primary key of the LeaseTemplate record}
        {--default-map : Overlay the DefaultLeasePdfCoordinateMap instead of the saved coordinate map}';

    protected $description = 'Generate a visual coordinate-map PDF for a lease template (stamps coloured boxes at every mapped position)';

    /**
     * Signature-slot keys: legacy keys + new 6-box signing-page keys.
     * Image slots are auto-detected by having 'width' without 'size',
     * but this explicit list is still used for colour assignment.
     */
    private const SIG_KEYS = [
        // Legacy
        'tenant_signature',
        'manager_signature',
        'witness_signature',
        'advocate_signature',
        'guarantor_signature',
        // New 6-box signing page
        'lessor_signature',
        'lessor_witness_signature',
        'lessor_advocate_signature',
        'lessor_advocate_stamp',
        'lessee_signature',
        'lessee_witness_signature',
        'lessee_advocate_signature',
        'lessee_advocate_stamp',
    ];

    /** Colour assignment per signature type [R, G, B]. */
    private const SIG_COLORS = [
        // Legacy
        'tenant_signature'         => [0,   122, 255],   // blue
        'manager_signature'        => [255,  59,  48],   // red
        'witness_signature'        => [52,  199,  89],   // green
        'advocate_signature'       => [255, 149,   0],   // orange
        'guarantor_signature'      => [175,  82, 222],   // purple
        // New 6-box signing page — lessor side (warm tones)
        'lessor_signature'         => [255,  59,  48],   // red (same as manager — lessor IS manager)
        'lessor_witness_signature' => [255, 149,   0],   // orange
        'lessor_advocate_signature'=> [255, 204,   0],   // yellow
        'lessor_advocate_stamp'    => [200, 160,   0],   // dark yellow
        // New 6-box signing page — lessee side (cool tones)
        'lessee_signature'         => [0,   122, 255],   // blue (same as tenant)
        'lessee_witness_signature' => [52,  199,  89],   // green
        'lessee_advocate_signature'=> [0,   199, 190],   // teal
        'lessee_advocate_stamp'    => [0,   150, 140],   // dark teal
    ];

    // -------------------------------------------------------------------------

    public function handle(PdfOverlayService $overlay): int
    {
        $templateId = $this->argument('template_id');

        // ── 1. Load template ─────────────────────────────────────────────────
        /** @var LeaseTemplate $template */
        $template = LeaseTemplate::find($templateId);

        if ($template === null) {
            $this->error("LeaseTemplate #{$templateId} not found.");
            return self::FAILURE;
        }

        $coordinateMap = $this->option('default-map')
            ? DefaultLeasePdfCoordinateMap::particularsPage1()
            : $template->pdf_coordinate_map;

        if (empty($coordinateMap)) {
            $this->error("Template #{$templateId} ({$template->name}) has no pdf_coordinate_map. Use --default-map to preview the DefaultLeasePdfCoordinateMap.");
            return self::FAILURE;
        }

        $source = $this->option('default-map') ? '<comment>DefaultLeasePdfCoordinateMap</comment>' : 'saved pdf_coordinate_map';
        $this->line("Template : <info>{$template->name}</info> (#{$template->id}, type: {$template->template_type})");
        $this->line("Map      : {$source}");
        $this->line('Coords   : ' . count($coordinateMap) . ' field(s) mapped');

        // ── 2. Resolve source PDF to a local temp path ───────────────────────
        $sourcePdfPath = $this->resolveSourcePdf($template);

        if ($sourcePdfPath === null) {
            $this->error('Could not resolve source PDF. Check source_pdf_path or DO Spaces credentials.');
            return self::FAILURE;
        }

        $this->line("Source   : {$sourcePdfPath}");

        // ── 3. Split coordinate map into text fields vs image/signature slots ──
        // Image slots are detected by having 'width' WITHOUT 'size' (the same
        // heuristic used in PdfOverlayService::stampAllSigningFields).
        $isImageSlot = static fn (array $c): bool => isset($c['width']) && ! isset($c['size']);

        $textCoords = array_filter($coordinateMap, static fn ($c) => is_array($c) && ! $isImageSlot($c));
        $sigCoords  = array_filter($coordinateMap, static fn ($c) => is_array($c) && $isImageSlot($c));

        // ── 4. Build dummy text values (field key as label) ──────────────────
        $textFields = [];
        foreach (array_keys($textCoords) as $key) {
            // Short label so it fits in the mapped box
            $textFields[$key] = strtoupper(str_replace('_', ' ', (string) $key));
        }

        // ── 5. Build coloured PNG rectangles for signature slots ─────────────
        $sigImages   = [];
        $tempPngPaths = [];

        foreach (array_keys($sigCoords) as $key) {
            [$r, $g, $b] = self::SIG_COLORS[$key] ?? [128, 128, 128];
            $dataUri     = $this->generateColoredBox($r, $g, $b);
            $pngPath     = $this->decodeToPng($dataUri, (string) $key);
            $sigImages[$key]  = $pngPath;
            $tempPngPaths[]   = $pngPath;
        }

        // ── 6. Prepare output directory ──────────────────────────────────────
        $outDir = storage_path('app/lease-pdf-overlay');
        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        // ── 7. Stamp text fields ─────────────────────────────────────────────
        $afterFields = $outDir . '/visual-map-fields-' . $template->id . '-' . uniqid() . '.pdf';

        if (! empty($textFields)) {
            $this->line('Stamping text fields…');
            $overlay->stampFields($sourcePdfPath, $textFields, $textCoords, $afterFields);
        } else {
            // No text fields — carry source forward unchanged
            copy($sourcePdfPath, $afterFields);
        }

        if (! file_exists($afterFields)) {
            $this->error('stampFields() produced no output file.');
            $this->cleanup($tempPngPaths);
            return self::FAILURE;
        }

        // ── 8. Stamp signature boxes ─────────────────────────────────────────
        $afterSigs = $outDir . '/visual-map-sigs-' . $template->id . '-' . uniqid() . '.pdf';

        if (! empty($sigImages)) {
            $this->line('Stamping signature boxes…');
            $overlay->stampMultipleSignatures($afterFields, $sigImages, $sigCoords, $afterSigs);
        } else {
            copy($afterFields, $afterSigs);
        }

        if (! file_exists($afterSigs)) {
            $this->error('stampMultipleSignatures() produced no output file.');
            $this->cleanup([$afterFields, ...$tempPngPaths]);
            return self::FAILURE;
        }

        // ── 9. Write artifact to project root ────────────────────────────────
        $finalBytes   = file_get_contents($afterSigs);
        $artifactPath = base_path('template-' . $template->id . '-mapped.pdf');
        $written      = file_put_contents($artifactPath, $finalBytes);

        if ($written === false || $written === 0) {
            $this->error("file_put_contents() wrote 0 bytes to {$artifactPath}.");
            $this->cleanup([$afterFields, $afterSigs, ...$tempPngPaths]);
            return self::FAILURE;
        }

        // ── 10. Clean up temp files ───────────────────────────────────────────
        $this->cleanup([$afterFields, $afterSigs, ...$tempPngPaths]);

        // Clean up temp source PDF if it was downloaded from Spaces
        if ($this->isTempSourcePdf($sourcePdfPath)) {
            @unlink($sourcePdfPath);
        }

        $kb = round(filesize($artifactPath) / 1024, 1);
        $this->newLine();
        $this->info("✓ template-{$template->id}-mapped.pdf → {$artifactPath} ({$kb} KB)");
        $this->line('');
        $this->line('Legend:');
        $this->line('  <fg=red>RED text</>                — text field labels at coordinate positions');
        $this->line('  Blue filled boxes         — lessee / tenant signature');
        $this->line('  Red filled boxes           — lessor / manager signature');
        $this->line('  Green filled boxes         — lessee / legacy witness signature');
        $this->line('  Orange filled boxes        — lessor witness / legacy advocate');
        $this->line('  Yellow / teal / purple     — advocate signatures and stamps');
        $sigCount = count($sigCoords);
        $txtCount = count($textCoords);
        $this->line("  ({$txtCount} text fields, {$sigCount} image/signature slots mapped)"  );

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve template source PDF to an absolute local filesystem path.
     *
     * In production, source_pdf_path is stored on DO Spaces; we download
     * the content to a local temp file and return that path so FPDI can
     * open it directly.  On local dev (FILESYSTEM_DISK=local) the file
     * lives under storage/app/private or storage/app.
     *
     * Returns null when the file cannot be found on any disk.
     */
    private function resolveSourcePdf(LeaseTemplate $template): ?string
    {
        $stored = $template->source_pdf_path;

        if (empty($stored)) {
            $this->warn('Template has no source_pdf_path.');
            return null;
        }

        // ── Try the configured default disk first (local dev) ─────────────
        $defaultDisk = config('filesystems.default', 'local');

        if (Storage::disk($defaultDisk)->exists($stored)) {
            $localPath = Storage::disk($defaultDisk)->path($stored);
            if (file_exists($localPath)) {
                return $localPath;
            }
        }

        // ── Try the 'local' disk explicitly ──────────────────────────────
        if (Storage::disk('local')->exists($stored)) {
            $localPath = Storage::disk('local')->path($stored);
            if (file_exists($localPath)) {
                return $localPath;
            }
        }

        // ── Try absolute path (already an absolute storage path) ──────────
        if (file_exists($stored)) {
            return $stored;
        }

        // ── Try storage/app prefix ────────────────────────────────────────
        $candidate = storage_path('app/' . $stored);
        if (file_exists($candidate)) {
            return $candidate;
        }

        // ── Try DO Spaces (production) ────────────────────────────────────
        try {
            if (Storage::disk('spaces')->exists($stored)) {
                $contents = Storage::disk('spaces')->get($stored);
                if ($contents !== null) {
                    $tmpPath = sys_get_temp_dir() . '/lease-vmap-' . $template->id . '-' . uniqid() . '.pdf';
                    file_put_contents($tmpPath, $contents);
                    $this->line("  (Downloaded from DO Spaces → {$tmpPath})");
                    return $tmpPath;
                }
            }
        } catch (\Throwable $e) {
            $this->warn('DO Spaces lookup failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Track which temp PDFs we downloaded from Spaces (they live in sys_get_temp_dir()).
     * Returns true when the path is in the system temp directory.
     */
    private function isTempSourcePdf(string $path): bool
    {
        return str_starts_with($path, sys_get_temp_dir());
    }

    /**
     * Generate a solid-colour PNG using PHP GD and return it as a data URI.
     *
     * @param int $r Red component   (0–255)
     * @param int $g Green component (0–255)
     * @param int $b Blue component  (0–255)
     */
    private function generateColoredBox(int $r, int $g, int $b): string
    {
        $image = imagecreatetruecolor(200, 100);
        $color = imagecolorallocate($image, $r, $g, $b);
        imagefilledrectangle($image, 0, 0, 200, 100, $color);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,' . base64_encode($imageData);
    }

    /**
     * Decode a base64 data URI to a real .png file on disk and return its path.
     *
     * PdfOverlayService requires real filesystem paths — FPDF::Image() cannot
     * consume data URIs.
     */
    private function decodeToPng(string $dataUri, string $label = 'stamp'): string
    {
        $base64    = (string) preg_replace('/^data:image\/\w+;base64,/', '', $dataUri);
        $imageData = base64_decode($base64);

        $dir = storage_path('app/signatures');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . '/' . $label . '-vmap-' . uniqid() . '.png';
        file_put_contents($path, $imageData);

        return $path;
    }

    /**
     * Delete an array of temporary files silently.
     *
     * @param string[] $paths
     */
    private function cleanup(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && $path !== '' && file_exists($path)) {
                @unlink($path);
            }
        }
    }
}
