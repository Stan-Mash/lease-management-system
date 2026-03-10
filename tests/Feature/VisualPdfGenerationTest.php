<?php

namespace Tests\Feature;

use App\Models\Landlord;
use App\Models\Lease;
use App\Models\LeaseTemplate;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\Zone;
use App\Services\PdfOverlayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Visual PDF artifact test.
 *
 * Generates a real, openable PDF with glaringly obvious colored stamp blocks
 * at known coordinates and writes it to base_path('visual-masterpiece.pdf')
 * using native file_put_contents() so it survives test teardown entirely.
 *
 * Run:
 *   php vendor/bin/phpunit tests/Feature/VisualPdfGenerationTest.php --no-coverage
 *
 * Then open:   visual-masterpiece.pdf   (in the project root directory)
 *
 * What to verify visually:
 *   - Red text labels stamped at their coordinate positions (tenant name, rent, etc.)
 *   - BLUE box  @ bottom-left  → tenant signature slot
 *   - RED box   @ bottom-right → manager/lessor signature slot
 *   - GREEN box @ lower-left   → witness signature slot
 */
class VisualPdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();
        Event::fake();
    }

    // -------------------------------------------------------------------------

    public function test_generates_visible_stamp_artifact_pdf(): void
    {
        // ── 1. Build minimal DB records ───────────────────────────────────────
        $zone     = Zone::factory()->create();
        $landlord = Landlord::factory()->create();
        $property = Property::factory()->create(['landlord_id' => $landlord->id]);
        $unit     = Unit::factory()->create(['property_id' => $property->id]);
        $tenant   = Tenant::factory()->create();

        // Coordinate map: text fields + three signature boxes, all on page 1.
        // Positions chosen well within A4 bounds (210 × 297 mm).
        $coordinateMap = [
            // ── text fields ──
            'tenant_name'      => ['page' => 1, 'x' => 55.0,  'y' => 60.0,  'width' => 80],
            'landlord_name'    => ['page' => 1, 'x' => 55.0,  'y' => 75.0,  'width' => 80],
            'monthly_rent'     => ['page' => 1, 'x' => 55.0,  'y' => 90.0,  'width' => 60],
            'start_date'       => ['page' => 1, 'x' => 55.0,  'y' => 105.0, 'width' => 60],
            'reference_number' => ['page' => 1, 'x' => 55.0,  'y' => 120.0, 'width' => 80],
            // ── signature slots ──
            'tenant_signature'  => ['page' => 1, 'x' => 20.0,  'y' => 155.0, 'width' => 70, 'height' => 35],
            'manager_signature' => ['page' => 1, 'x' => 110.0, 'y' => 155.0, 'width' => 70, 'height' => 35],
            'witness_signature' => ['page' => 1, 'x' => 20.0,  'y' => 215.0, 'width' => 70, 'height' => 35],
        ];

        // Create template directly (no LeaseTemplateFactory exists)
        $template = LeaseTemplate::create([
            'name'               => 'Visual Test Template',
            'slug'               => 'visual-test-template-' . uniqid(),
            'template_type'      => 'commercial',
            'source_type'        => 'uploaded_pdf',
            'blade_content'      => '',     // NOT NULL column — empty for PDF-overlay templates
            'source_pdf_path'    => null,   // filled in below after we write the source file
            'pdf_coordinate_map' => $coordinateMap,
            'is_active'          => true,
            'is_default'         => false,
            'version_number'     => 1,
        ]);

        $lease = Lease::factory()->create([
            'workflow_state'    => 'tenant_signed',
            'tenant_id'         => $tenant->id,
            'landlord_id'       => $landlord->id,
            'property_id'       => $property->id,
            'unit_id'           => $unit->id,
            'zone_id'           => $zone->id,
            'monthly_rent'      => '75000.00',
            'deposit_amount'    => '150000.00',
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addYear()->toDateString(),
            'lease_template_id' => $template->id,
            'reference_number'  => 'VIS-TEST-001',
        ]);

        // ── 2. Build a structurally valid A4 source PDF via FPDF ──────────────
        $sourcePdfPath = $this->buildMinimalA4Pdf();
        $this->assertFileExists($sourcePdfPath, 'Source PDF was not created');

        // Point the template record at the source file we just wrote
        $template->update(['source_pdf_path' => 'lease-pdf-overlay/' . basename($sourcePdfPath)]);

        // ── 3. Decode PNGs to real files (PdfOverlayService needs file paths) ─
        $bluePng  = $this->pngToTempFile($this->testPng(200, 100, 0,   122, 255), 'stamp-blue');
        $redPng   = $this->pngToTempFile($this->testPng(200, 100, 255,  59,  48), 'stamp-red');
        $greenPng = $this->pngToTempFile($this->testPng(200, 100,  52, 199,  89), 'stamp-green');

        foreach (['blue' => $bluePng, 'red' => $redPng, 'green' => $greenPng] as $color => $path) {
            $this->assertFileExists($path, "Stamp PNG ({$color}) was not written to disk");
        }

        // ── 4. Run PdfOverlayService ──────────────────────────────────────────
        $overlay = app(PdfOverlayService::class);
        $outDir  = storage_path('app/lease-pdf-overlay');
        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        // Step A: stamp text fields (red overlay text at the coordinate positions)
        $sigKeys    = ['tenant_signature', 'manager_signature', 'witness_signature', 'advocate_signature', 'guarantor_signature'];
        $textCoords = array_filter(
            $coordinateMap,
            fn ($c, $k) => ! in_array((string) $k, $sigKeys, true),
            ARRAY_FILTER_USE_BOTH
        );
        $textFields = [
            'tenant_name'      => $tenant->names,
            'landlord_name'    => $landlord->names,
            'monthly_rent'     => number_format(75000.00, 2),
            'start_date'       => now()->format('d-m-Y'),
            'reference_number' => 'VIS-TEST-001',
        ];

        $afterFields = $outDir . '/visual-step1-fields.pdf';
        $overlay->stampFields($sourcePdfPath, $textFields, $textCoords, $afterFields);
        $this->assertFileExists($afterFields, 'stampFields() produced no output file');

        // Step B: stamp all three colored signature blocks in one FPDI pass
        $afterSigs = $outDir . '/visual-step2-sigs.pdf';
        $overlay->stampMultipleSignatures(
            $afterFields,
            [
                'tenant_signature'  => $bluePng,
                'manager_signature' => $redPng,
                'witness_signature' => $greenPng,
            ],
            [
                'tenant_signature'  => $coordinateMap['tenant_signature'],
                'manager_signature' => $coordinateMap['manager_signature'],
                'witness_signature' => $coordinateMap['witness_signature'],
            ],
            $afterSigs,
        );
        $this->assertFileExists($afterSigs, 'stampMultipleSignatures() produced no output file');

        // ── 5. Verify output looks like a real PDF ────────────────────────────
        $finalBytes = file_get_contents($afterSigs);
        $this->assertNotEmpty($finalBytes, 'Final PDF bytes are empty');
        $this->assertStringStartsWith('%PDF', $finalBytes, 'Output does not start with %PDF header');

        // ── 6. Write to repo root — bypasses ALL Laravel storage / teardown ───
        //    file_put_contents() is a native PHP call that has no relationship
        //    to Storage::fake(), RefreshDatabase, or any framework lifecycle.
        $artifactPath = base_path('visual-masterpiece.pdf');
        $written      = file_put_contents($artifactPath, $finalBytes);

        $this->assertGreaterThan(0, $written, 'file_put_contents() wrote 0 bytes to visual-masterpiece.pdf');
        $this->assertFileExists($artifactPath, 'Artifact not found at repo root after write');

        // ── 7. Tidy temp files (the artifact itself is intentionally kept) ────
        foreach ([$sourcePdfPath, $afterFields, $afterSigs, $bluePng, $redPng, $greenPng] as $tmp) {
            @unlink($tmp);
        }

        $kb = round(filesize($artifactPath) / 1024, 1);
        fwrite(STDOUT, PHP_EOL . "  ✓ visual-masterpiece.pdf  →  {$artifactPath}  ({$kb} KB)" . PHP_EOL);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a highly visible solid colored PNG for visual PDF verification.
     */
    protected function testPng(int $width = 200, int $height = 100, int $r = 0, int $g = 122, int $b = 255): string
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, $r, $g, $b);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,' . base64_encode($imageData);
    }

    /**
     * Decode a base64 data URI to a real .png file on disk and return its path.
     *
     * PdfOverlayService::stampSignature() and stampMultipleSignatures() require
     * real filesystem paths — they call file_exists() and FPDF::Image() which
     * cannot consume data URIs.
     */
    private function pngToTempFile(string $dataUri, string $label = 'stamp'): string
    {
        $base64    = preg_replace('/^data:image\/\w+;base64,/', '', $dataUri);
        $imageData = base64_decode($base64);

        $dir = storage_path('app/signatures');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . '/' . $label . '-' . uniqid() . '.png';
        file_put_contents($path, $imageData);

        return $path;
    }

    /**
     * Build a structurally valid single-page A4 PDF using FPDF.
     *
     * Raw %PDF-1.4 stub strings fail FPDI's cross-reference parser.
     * Using FPDF itself guarantees a well-formed file that FPDI can import.
     * The page includes labeled boxes so we can immediately see whether stamp
     * coordinates are landing in the right places.
     *
     * @return string Absolute path to the written source PDF
     */
    private function buildMinimalA4Pdf(): string
    {
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // Light gray page background
        $pdf->SetFillColor(245, 245, 245);
        $pdf->Rect(0, 0, 210, 297, 'F');

        // ── Title ──
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->SetTextColor(50, 50, 50);
        $pdf->SetXY(20, 20);
        $pdf->Cell(170, 10, 'VISUAL STAMP TEST - LEASE AGREEMENT', 0, 1, 'C');

        // Subtitle
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->SetXY(20, 33);
        $pdf->Cell(170, 6, 'VisualPdfGenerationTest: red text + colored boxes will be stamped at labeled positions', 0, 1, 'C');

        // ── Horizontal rule ──
        $pdf->SetDrawColor(200, 160, 32);
        $pdf->SetLineWidth(0.8);
        $pdf->Line(20, 42, 190, 42);

        // ── Field label guides ──
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(160, 160, 160);
        $fieldLabels = [
            [55, 60,  'Tenant name → RED TEXT here'],
            [55, 75,  'Landlord name → RED TEXT here'],
            [55, 90,  'Monthly rent → RED TEXT here'],
            [55, 105, 'Start date → RED TEXT here'],
            [55, 120, 'Reference → RED TEXT here'],
        ];
        foreach ($fieldLabels as [$x, $y, $label]) {
            // Label to the left
            $pdf->SetXY(20, $y - 0.5);
            $pdf->Cell(32, 5, substr($label, 0, strpos($label, ' →')), 0, 0, 'R');
            // Ghost placeholder box
            $pdf->SetDrawColor(200, 200, 200);
            $pdf->SetLineWidth(0.2);
            $pdf->Rect($x, $y - 1, 80, 6);
        }

        // ── Signature area outlines ──
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->SetLineWidth(0.4);

        $sigSlots = [
            [20,  145, 70, 35, 'TENANT (BLUE BOX)'],
            [110, 145, 70, 35, 'MANAGER (RED BOX)'],
            [20,  205, 70, 35, 'WITNESS (GREEN BOX)'],
        ];
        foreach ($sigSlots as [$x, $y, $w, $h, $label]) {
            $pdf->SetXY($x, $y);
            $pdf->Cell($w, 5, $label, 0, 0, 'C');
            $pdf->Rect($x, $y + 6, $w, $h);
        }

        // ── Footer ──
        $pdf->SetFont('Helvetica', 'I', 7);
        $pdf->SetTextColor(180, 180, 180);
        $pdf->SetXY(20, 280);
        $pdf->Cell(170, 5, 'Chabrin Agencies Ltd — Visual Test Artifact — ' . date('Y-m-d H:i:s'), 0, 0, 'C');

        // Write to temp location inside storage (will be removed after test)
        $outDir = storage_path('app/lease-pdf-overlay');
        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }
        $path = $outDir . '/visual-source-' . uniqid() . '.pdf';
        $pdf->Output('F', $path);

        return $path;
    }
}
