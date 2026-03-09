<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DigitalSignature;
use App\Models\Lease;
use InvalidArgumentException;
use setasign\Fpdi\Fpdi;

/**
 * Stamp text and images onto an existing PDF (e.g. landlord-provided lease).
 * Uses FPDI to import the source PDF and FPDF API to draw at (x, y) coordinates.
 * Coordinates are in mm; font size in pt.
 *
 * Font notes:
 *   Default overlay font is Helvetica (built-in FPDF). To use Century Gothic:
 *   1. Copy C:\Windows\Fonts\GOTHIC.TTF to storage/app/fonts/CenturyGothic.ttf
 *   2. Run: php artisan tinker -r "FPDF_AddFont_Helper::make()"  (or use the MakeFont utility)
 *   3. This generates storage/app/fonts/centurygothic.php + centurygothic.z
 *   4. Set OVERLAY_FONT=centurygothic in .env  (optional - falls back to Helvetica)
 *
 * Default fill color is dark red (#C00000) so filled values are clearly distinct
 * from the template's black text. Override per-field via the 'color' key in coordinates.
 */
class PdfOverlayService
{
    /** Default color for stamped text — dark red for clear visual distinction */
    private const DEFAULT_COLOR = 'C00000';

    public function __construct()
    {
        // FPDF reads FPDF_FONTPATH in its constructor, so the constant must be defined
        // before any Fpdi instance is created. Define it here, at service construction time.
        $fontDir  = storage_path('app/fonts');
        $fontFile = $fontDir . '/centurygothic.php';
        if (file_exists($fontFile) && ! defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', $fontDir . '/');
        }
    }

    /**
     * Stamp text fields onto an uploaded PDF template.
     * Page dimensions are auto-detected from the source PDF (supports Letter, A4, etc.)
     *
     * @param array<string, string> $fields ['tenant_name' => 'John Doe', ...]
     * @param array<string, array{page: int, x: float, y: float, size?: int, color?: string}> $coordinates
     */
    public function stampFields(
        string $sourcePdfPath,
        array $fields,
        array $coordinates,
        string $outputPath,
    ): string {
        $pdf = new Fpdi();
        $this->loadFont($pdf);
        $pageCount = $pdf->setSourceFile($sourcePdfPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tpl = $pdf->importPage($pageNo);
            // Auto-detect source page dimensions (Letter, A4, etc.)
            $size = $pdf->getTemplateSize($tpl);
            $pdf->AddPage('P', [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

            foreach ($coordinates as $fieldKey => $config) {
                $page = (int) ($config['page'] ?? 1);
                if ($page !== $pageNo) {
                    continue;
                }
                $value = $fields[$fieldKey] ?? '';
                if ($value === '') {
                    continue;
                }
                $x        = (float) ($config['x'] ?? 0);
                $y        = (float) ($config['y'] ?? 0);
                $fontSize = (int) ($config['size'] ?? 10);
                $color    = $config['color'] ?? self::DEFAULT_COLOR;
                $this->writeText($pdf, $value, $x, $y, $fontSize, $color);
            }
        }

        $pdf->Output('F', $outputPath);

        return $outputPath;
    }

    /**
     * Stamp a signature PNG onto a specific page/position.
     * anchor: 'above' = place image so its bottom is at y (Lessor/Agent above text line); 'beside' = use x,y as-is (Advocate beside text); default = top-left at (x,y).
     */
    public function stampSignature(
        string $sourcePdfPath,
        string $signaturePngPath,
        int $page,
        float $x,
        float $y,
        float $width,
        float $height,
        string $outputPath,
        string $anchor = 'default',
    ): string {
        if (! file_exists($signaturePngPath)) {
            throw new InvalidArgumentException("Signature file not found: {$signaturePngPath}");
        }

        if ($anchor === 'above') {
            $y = $y - $height;
        }

        $pdf = new Fpdi('P', 'mm', 'A4');
        $pageCount = $pdf->setSourceFile($sourcePdfPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tpl  = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tpl);
            $pdf->AddPage('P', [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);
            if ($pageNo === $page) {
                $pdf->Image($signaturePngPath, $x, $y, $width, $height);
            }
        }

        $pdf->Output('F', $outputPath);

        return $outputPath;
    }

    /**
     * Stamp multiple signature PNGs onto a PDF in a single FPDI pass.
     *
     * @param array<string,string> $images      Keyed by logical name, e.g. 'lessee_witness' => '/path/to.png'
     * @param array<string,array{page:int,x:float,y:float,width:float,height:float}> $coordinates
     */
    public function stampMultipleSignatures(
        string $sourcePdfPath,
        array $images,
        array $coordinates,
        string $outputPath,
    ): string {
        $pdf = new Fpdi('P', 'mm', 'A4');
        $pageCount = $pdf->setSourceFile($sourcePdfPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tpl  = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tpl);
            $pdf->AddPage('P', [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

            foreach ($images as $key => $imagePath) {
                if (! isset($coordinates[$key])) {
                    continue;
                }
                if (! is_string($imagePath) || $imagePath === '' || ! file_exists($imagePath)) {
                    continue;
                }

                $config = $coordinates[$key];
                $targetPage = (int) ($config['page'] ?? 1);
                if ($targetPage !== $pageNo) {
                    continue;
                }

                $x = (float) ($config['x'] ?? 0.0);
                $y = (float) ($config['y'] ?? 0.0);
                $w = (float) ($config['width'] ?? 0.0);
                $h = (float) ($config['height'] ?? 0.0);

                $pdf->Image($imagePath, $x, $y, $w, $h);
            }
        }

        $pdf->Output('F', $outputPath);

        return $outputPath;
    }

    /**
     * Stamp manager countersignature + timestamp + SHA-256 audit stamp.
     */
    public function stampAuditBlock(
        string $pdfPath,
        Lease $lease,
        DigitalSignature $tenantSig,
        DigitalSignature $managerSig,
        string $outputPath,
    ): string {
        $ref         = $lease->reference_number ?? 'N/A';
        $hashPrefix  = $managerSig->verification_hash
            ? substr($managerSig->verification_hash, 0, 16)
            : 'N/A';
        $timestamp = $managerSig->signed_at?->format('d M Y, H:i') ?? now()->format('d M Y, H:i');

        $lines = [
            'Digitally executed — Ref: ' . $ref,
            'SHA-256: ' . $hashPrefix,
            'Chabrin Agencies Ltd — ' . $timestamp,
        ];
        $block = implode("\n", $lines);

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($pdfPath);
        $lastPage  = $pageCount;

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tpl  = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tpl);
            $pdf->AddPage('P', [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);
            if ($pageNo === $lastPage) {
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->SetTextColor(80, 80, 80);
                $pdf->SetXY(120, $size['height'] - 20);
                $pdf->MultiCell(80, 4, $block, 0, 'R');
            }
        }

        $pdf->Output('F', $outputPath);

        return $outputPath;
    }

    /**
     * Load the overlay font. Uses Century Gothic if pre-generated font files exist in
     * storage/app/fonts/; otherwise falls back to Helvetica.
     *
     * To set up Century Gothic:
     *   cp "C:\Windows\Fonts\GOTHIC.TTF" storage/app/fonts/
     *   php vendor/setasign/fpdf/makefont/makefont.php storage/app/fonts/GOTHIC.TTF cp1252
     *   mv centurygothic.* storage/app/fonts/
     */
    private function loadFont(Fpdi $pdf): void
    {
        $fontDir  = storage_path('app/fonts');
        $fontFile = $fontDir . '/centurygothic.php';

        if (file_exists($fontFile)) {
            // Pass $dir explicitly so FPDF uses our storage path regardless of
            // whether FPDF_FONTPATH is defined (it's a PHP constant — unreliable
            // across FPM workers/requests if not set before first Fpdi construction).
            $pdf->AddFont('CenturyGothic', '', 'centurygothic.php', $fontDir . '/');
            if (file_exists($fontDir . '/centurygothicb.php')) {
                $pdf->AddFont('CenturyGothic', 'B', 'centurygothicb.php', $fontDir . '/');
            }
            return;
        }

        // Helvetica is always available as FPDF built-in
        // (visually similar at small sizes; Century Gothic setup documented above)
    }

    private function writeText(Fpdi $pdf, string $text, float $x, float $y, int $fontSize, string $colorHex): void
    {
        $r = (int) hexdec(substr($colorHex, 0, 2));
        $g = (int) hexdec(substr($colorHex, 2, 2));
        $b = (int) hexdec(substr($colorHex, 4, 2));

        $fontDir  = storage_path('app/fonts');
        $fontName = file_exists($fontDir . '/centurygothic.php') ? 'CenturyGothic' : 'Helvetica';

        $pdf->SetFont($fontName, '', $fontSize);
        $pdf->SetTextColor($r, $g, $b);
        $pdf->SetXY($x, $y);
        $pdf->Cell(0, 5, $text);
    }
}
