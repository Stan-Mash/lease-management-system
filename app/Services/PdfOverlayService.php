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
 */
class PdfOverlayService
{
    /**
     * Stamp text fields onto an uploaded PDF template.
     *
     * @param array<string, string> $fields ['tenant_name' => 'John Doe', 'unit_code' => '484A-001', ...]
     * @param array<string, array{page: int, x: float, y: float, size?: int, color?: string}> $coordinates field => [page, x, y, fontSize?, fontColor?]
     */
    public function stampFields(
        string $sourcePdfPath,
        array $fields,
        array $coordinates,
        string $outputPath,
    ): string {
        $pdf = new Fpdi('P', 'mm', 'A4');
        $pageCount = $pdf->setSourceFile($sourcePdfPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tpl = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($tpl);

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
                $fontSize = (int) ($config['size'] ?? 11);
                $colorHex = $config['color'] ?? '000000';
                $this->writeText($pdf, $value, $x, $y, $fontSize, $colorHex);
            }
        }

        $pdf->Output('F', $outputPath);

        return $outputPath;
    }

    /**
     * Stamp the tenant's drawn signature PNG onto a specific page/position.
     * The signature PNG path comes from DigitalSignature->writeSignatureTempFile() or equivalent.
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
    ): string {
        if (! file_exists($signaturePngPath)) {
            throw new InvalidArgumentException("Signature file not found: {$signaturePngPath}");
        }

        $pdf = new Fpdi('P', 'mm', 'A4');
        $pageCount = $pdf->setSourceFile($sourcePdfPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tpl = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($tpl);
            if ($pageNo === $page) {
                $pdf->Image($signaturePngPath, $x, $y, $width, $height);
            }
        }

        $pdf->Output('F', $outputPath);

        return $outputPath;
    }

    /**
     * Stamp manager countersignature + timestamp + SHA-256 audit stamp.
     * The audit stamp is a small text block in the bottom-right corner:
     *   "Digitally executed — Ref: CH-MAJ-484A-001-2026-001
     *    SHA-256: [first 16 chars of verification_hash]
     *    Chabrin Agencies Ltd — [timestamp]"
     */
    public function stampAuditBlock(
        string $pdfPath,
        Lease $lease,
        DigitalSignature $tenantSig,
        DigitalSignature $managerSig,
        string $outputPath,
    ): string {
        $ref = $lease->reference_number ?? 'N/A';
        $hashPrefix = $managerSig->verification_hash
            ? substr($managerSig->verification_hash, 0, 16)
            : 'N/A';
        $timestamp = $managerSig->signed_at?->format('d M Y, H:i') ?? now()->format('d M Y, H:i');

        $lines = [
            'Digitally executed — Ref: ' . $ref,
            'SHA-256: ' . $hashPrefix,
            'Chabrin Agencies Ltd — ' . $timestamp,
        ];
        $block = implode("\n", $lines);

        $pdf = new Fpdi('P', 'mm', 'A4');
        $pageCount = $pdf->setSourceFile($pdfPath);
        $lastPage = $pageCount;

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tpl = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($tpl);
            if ($pageNo === $lastPage) {
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->SetTextColor(80, 80, 80);
                $pdf->SetXY(120, 275);
                $pdf->MultiCell(80, 4, $block, 0, 'R');
            }
        }

        $pdf->Output('F', $outputPath);

        return $outputPath;
    }

    private function writeText(Fpdi $pdf, string $text, float $x, float $y, int $fontSize, string $colorHex): void
    {
        $r = (int) hexdec(substr($colorHex, 0, 2));
        $g = (int) hexdec(substr($colorHex, 2, 2));
        $b = (int) hexdec(substr($colorHex, 4, 2));
        $pdf->SetFont('Helvetica', '', $fontSize);
        $pdf->SetTextColor($r, $g, $b);
        $pdf->SetXY($x, $y);
        $pdf->Cell(0, 5, $text);
    }
}
