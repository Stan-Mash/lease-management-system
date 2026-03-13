<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DigitalSignature;
use App\Models\Lease;
use InvalidArgumentException;
use setasign\Fpdi\Fpdi;

/**
 * Stamp text and images onto an existing PDF (e.g. landlord-provided lease).
 * Uses FPDI (setasign/Fpdi) to import the source PDF and FPDF API to draw at (x, y) coordinates.
 * Coordinates are in mm; font size 12pt; font Century Gothic Bold; color pure red (#FF0000).
 * Per-field optional keys: size, color, width (mm), align (L|C|R).
 * Use width so text stays within the printed box; align controls horizontal alignment within that width.
 *
 * Font: TrueType at storage_path('fonts/centurygothic_bold.ttf'). FPDF requires converted .php/.z;
 * generate with: php vendor/setasign/fpdf/makefont/makefont.php storage/fonts/centurygothic_bold.ttf
 * and place centurygothic_bold.php (and .z if present) in storage/fonts/. Falls back to Helvetica Bold if missing.
 */
class PdfOverlayService
{
    /** Text color for stamped values — pure red (R:255, G:0, B:0). */
    private const DEFAULT_COLOR = 'FF0000';

    /** Font size in pt for all overlay text. */
    private const OVERLAY_FONT_SIZE = 12;

    /** Y-offset in mm applied only to fields that sit above their baseline (selective alignment). */
    private const Y_OFFSET_FOR_BASELINE_MM = 1;

    /** Field keys that receive the selective Y-offset; all others use coordinates as-is. */
    private const FIELDS_WITH_Y_OFFSET = [
        'lease_date_day',
        'lease_date_month',
        'lease_date_year',
        'landlord_name',
        'landlord_po_box',
        'tenant_name',
        'tenant_id_number',
        'tenant_po_box',
        'start_date_day',
        'start_date_month',
        'start_date_year',
        'end_date_day',
        'end_date_month',
        'end_date_year',
        'lease_years',
        'lease_months',
        'lease_duration_months',
        'grant_of_lease_duration',
        'monthly_rent',
        'deposit_amount',
        'vat_amount',
        'rent_review_years',
        'rent_review_rate',
        'property_name',
        'property_lr_number',
        'unit_code',
        'start_date',
        'end_date',
        'reference_number',
    ];

    public function __construct()
    {
        $fontDir = storage_path('fonts');
        $fontFile = $fontDir . '/centurygothic_bold.php';
        if (! file_exists($fontFile)) {
            $fontDir = storage_path('app/fonts');
            $fontFile = $fontDir . '/centurygothic.php';
        }
        if (file_exists($fontFile) && ! defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', $fontDir . '/');

            // FPDF_FONTPATH overrides the search path for ALL fonts including core fonts
            // (helvetica, courier, times). If those .php descriptors are absent from the
            // custom font dir, any SetFont('Helvetica') call will fail with an include error.
            // Copy missing core font descriptors from the FPDF vendor bundle on first use.
            $vendorFontDir = base_path('vendor/setasign/fpdf/font');
            if (is_dir($vendorFontDir)) {
                foreach (glob($vendorFontDir . '/*.php') ?: [] as $vendorFont) {
                    $dest = $fontDir . '/' . basename($vendorFont);
                    if (! file_exists($dest)) {
                        @copy($vendorFont, $dest);
                    }
                }
            }
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
        // Disable auto-page-break so an out-of-bounds y coordinate in the map never
        // silently inserts extra blank pages into the generated document.
        $pdf->SetAutoPageBreak(false);
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
                $x = (float) ($config['x'] ?? 0);
                $y = (float) ($config['y'] ?? 0);
                if (in_array((string) $fieldKey, self::FIELDS_WITH_Y_OFFSET, true)) {
                    $y += self::Y_OFFSET_FOR_BASELINE_MM;
                }
                $fontSize = self::OVERLAY_FONT_SIZE;
                $color    = self::DEFAULT_COLOR;
                $widthMm  = isset($config['width']) ? (float) $config['width'] : null;
                $align    = $config['align'] ?? 'L';
                $this->writeText($pdf, $value, $x, $y, $fontSize, $color, $widthMm, $align);
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

        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
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
     * Apply advocate signature and optional stamp (e.g. Commissioner for Oaths) to the PDF.
     * Stamp is drawn first (slightly left of the signature box), then the signature.
     *
     * @param  array{page: int, x: float, y: float, width: float, height: float, anchor?: string}  $advocateCoord  From template pdf_coordinate_map['advocate_signature']
     */
    public function applyAdvocateSignatureAndStamp(
        string $sourcePdfPath,
        string $signaturePath,
        ?string $stampPath,
        array $advocateCoord,
        string $outputPath,
    ): string {
        if (! file_exists($signaturePath)) {
            throw new InvalidArgumentException("Signature file not found: {$signaturePath}");
        }

        $pageNum = (int) ($advocateCoord['page'] ?? 1);
        $x       = (float) ($advocateCoord['x'] ?? 160);
        $y       = (float) ($advocateCoord['y'] ?? 250);
        $width   = (float) ($advocateCoord['width'] ?? 45);
        $height  = (float) ($advocateCoord['height'] ?? 18);
        $anchor  = (string) ($advocateCoord['anchor'] ?? 'beside');

        if ($anchor === 'above') {
            $y = $y - $height;
        }

        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
        $pageCount = $pdf->setSourceFile($sourcePdfPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tpl  = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tpl);
            $pdf->AddPage('P', [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

            if ($pageNo === $pageNum) {
                if ($stampPath !== null && $stampPath !== '' && file_exists($stampPath)) {
                    $stampWidth  = min(25, $width * 0.6);
                    $stampHeight = min(20, $height * 1.1);
                    $stampX      = $x - $stampWidth - 5;
                    $pdf->Image($stampPath, $stampX, $y, $stampWidth, $stampHeight);
                }
                $pdf->Image($signaturePath, $x, $y, $width, $height);
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
        $pdf->SetAutoPageBreak(false);
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
     * Stamp all signing-page fields (text + images) in a single FPDI pass.
     *
     * Handles both text fields and image fields from the coordinate map in one pass,
     * avoiding multiple re-imports of the same PDF.  Fields with no value/path are
     * silently skipped so partial data (e.g. only tenant signed so far) works fine.
     *
     * Image entries are distinguished from text entries by the presence of a
     * 'width' key WITHOUT a 'size' key (text entries always have 'size').
     *
     * @param  array<string, string>  $textFields   Keyed by coord map key, e.g. ['lessor_sig_name' => 'John Doe']
     * @param  array<string, string>  $imagePaths   Keyed by coord map key, e.g. ['lessor_signature' => '/tmp/sig.png']
     * @param  array<string, array{page: int, x: float, y: float, ...}>  $coordinates
     */
    public function stampAllSigningFields(
        string $sourcePdfPath,
        array $textFields,
        array $imagePaths,
        array $coordinates,
        string $outputPath,
    ): string {
        if (empty($textFields) && empty($imagePaths)) {
            copy($sourcePdfPath, $outputPath);
            return $outputPath;
        }

        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
        $this->loadFont($pdf);
        $pageCount = $pdf->setSourceFile($sourcePdfPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tpl  = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tpl);
            $pdf->AddPage('P', [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

            foreach ($coordinates as $fieldKey => $config) {
                $targetPage = (int) ($config['page'] ?? 1);
                if ($targetPage !== $pageNo) {
                    continue;
                }

                $isImage = isset($config['width']) && ! isset($config['size']);

                if ($isImage) {
                    $imgPath = $imagePaths[$fieldKey] ?? null;
                    if ($imgPath === null || $imgPath === '' || ! file_exists($imgPath)) {
                        continue;
                    }
                    $x      = (float) ($config['x'] ?? 0);
                    $y      = (float) ($config['y'] ?? 0);
                    $w      = (float) ($config['width'] ?? 0);
                    $h      = (float) ($config['height'] ?? 0);
                    $anchor = (string) ($config['anchor'] ?? 'default');
                    if ($anchor === 'above') {
                        $y = $y - $h;
                    }
                    try {
                        $pdf->Image($imgPath, $x, $y, $w, $h);
                    } catch (\Exception $e) {
                        // Bad image — skip silently to avoid aborting the whole pass
                    }
                } else {
                    $value = $textFields[$fieldKey] ?? '';
                    if ($value === '') {
                        continue;
                    }
                    $x        = (float) ($config['x'] ?? 0);
                    $y        = (float) ($config['y'] ?? 0);
                    $fontSize = isset($config['size']) ? (int) $config['size'] : self::OVERLAY_FONT_SIZE;
                    $color    = isset($config['color']) ? (string) $config['color'] : self::DEFAULT_COLOR;
                    $widthMm  = isset($config['width']) ? (float) $config['width'] : null;
                    $align    = $config['align'] ?? 'L';
                    $this->writeText($pdf, $value, $x, $y, $fontSize, $color, $widthMm, $align);
                }
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
        $pdf->SetAutoPageBreak(false);
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
     * Stamp small date labels (e.g. "Date: 13/03/2026") below each signature image.
     * Single FPDI pass; 8pt Helvetica, dark gray (80,80,80).
     *
     * @param array<int, array{text: string, page: int, x: float, y: float}> $dateEntries
     */
    public function stampDateTexts(
        string $sourcePdfPath,
        array $dateEntries,
        string $outputPath,
    ): string {
        if (empty($dateEntries)) {
            if ($sourcePdfPath !== $outputPath) {
                copy($sourcePdfPath, $outputPath);
            }

            return $outputPath;
        }

        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
        $pageCount = $pdf->setSourceFile($sourcePdfPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tpl  = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tpl);
            $pdf->AddPage('P', [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

            foreach ($dateEntries as $entry) {
                if ((int) ($entry['page'] ?? 1) !== $pageNo) {
                    continue;
                }
                $pdf->SetFont('Helvetica', '', 8);
                $pdf->SetTextColor(80, 80, 80);
                $pdf->SetXY((float) $entry['x'], (float) $entry['y']);
                $pdf->Cell(60, 4, (string) $entry['text'], 0, 0, 'L');
            }
        }

        $pdf->Output('F', $outputPath);

        return $outputPath;
    }

    /**
     * Load overlay font: Century Gothic Bold from storage_path('fonts/centurygothic_bold.php').
     * That file is generated from storage_path('fonts/centurygothic_bold.ttf') via FPDF makefont.
     * Falls back to storage/app/fonts (centurygothic.php / centurygothicb.php) then Helvetica Bold.
     */
    private function loadFont(Fpdi $pdf): void
    {
        $fontDirBold = storage_path('fonts');
        $fontFileBold = $fontDirBold . '/centurygothic_bold.php';

        if (file_exists($fontFileBold)) {
            $pdf->AddFont('CenturyGothic', 'B', 'centurygothic_bold.php', $fontDirBold . '/');
            return;
        }

        $fontDir = storage_path('app/fonts');
        $fontFile = $fontDir . '/centurygothic.php';
        if (file_exists($fontFile)) {
            $pdf->AddFont('CenturyGothic', '', 'centurygothic.php', $fontDir . '/');
            if (file_exists($fontDir . '/centurygothicb.php')) {
                $pdf->AddFont('CenturyGothic', 'B', 'centurygothicb.php', $fontDir . '/');
            }
            return;
        }

        // Helvetica Bold is always available as FPDF built-in
    }

    /**
     * Write text at (x,y). Century Gothic Bold, 12pt, pure red. Optional width (mm) and align (L/C/R).
     */
    private function writeText(
        Fpdi $pdf,
        string $text,
        float $x,
        float $y,
        int $fontSize,
        string $colorHex,
        ?float $widthMm = null,
        string $align = 'L',
    ): void {
        $r = (int) hexdec(substr($colorHex, 0, 2));
        $g = (int) hexdec(substr($colorHex, 2, 2));
        $b = (int) hexdec(substr($colorHex, 4, 2));

        $fontDirBold   = storage_path('fonts');
        $hasCGBoldNew  = file_exists($fontDirBold . '/centurygothic_bold.php');
        $fontDir       = storage_path('app/fonts');
        $hasCG         = file_exists($fontDir . '/centurygothic.php');
        $hasCGBoldOld  = file_exists($fontDir . '/centurygothicb.php');

        if ($hasCGBoldNew) {
            $fontName  = 'CenturyGothic';
            $fontStyle = 'B';
        } elseif ($hasCG && $hasCGBoldOld) {
            $fontName  = 'CenturyGothic';
            $fontStyle = 'B';
        } else {
            $fontName  = 'Helvetica';
            $fontStyle = 'B';
        }

        $pdf->SetFont($fontName, $fontStyle, $fontSize);
        $pdf->SetTextColor($r, $g, $b);
        $pdf->SetXY($x, $y);

        $align = strtoupper($align);
        if (! in_array($align, ['L', 'C', 'R'], true)) {
            $align = 'L';
        }

        if ($widthMm !== null && $widthMm > 0) {
            $pdf->Cell($widthMm, 5, $text, 0, 0, $align);
        } else {
            $pdf->Cell(0, 5, $text, 0, 0, $align);
        }
    }
}
