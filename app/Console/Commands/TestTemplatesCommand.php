<?php

namespace App\Console\Commands;

use App\Models\LeaseTemplate;
use App\Services\SampleLeaseDataService;
use App\Services\TemplateRenderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestTemplatesCommand extends Command
{
    protected $signature = 'templates:test {--template= : Test specific template by ID} {--output=storage/app/test-pdfs : Output directory}';

    protected $description = 'Test all lease templates with sample data and generate PDFs';

    protected TemplateRenderService $templateRenderer;

    public function __construct(TemplateRenderService $templateRenderer)
    {
        parent::__construct();
        $this->templateRenderer = $templateRenderer;
    }

    public function handle(): int
    {
        $this->info('🧪 Testing Lease Templates...');
        $this->newLine();

        $templateId = $this->option('template');
        $outputDir = $this->option('output');

        // Ensure output directory exists
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Get templates to test
        $templates = $templateId
            ? LeaseTemplate::where('id', $templateId)->get()
            : LeaseTemplate::where('is_active', true)->get();

        if ($templates->isEmpty()) {
            $this->error('❌ No templates found to test!');
            return self::FAILURE;
        }

        $this->info("Found {$templates->count()} template(s) to test:");
        $this->newLine();

        $successCount = 0;
        $failureCount = 0;

        foreach ($templates as $template) {
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("Testing: {$template->name}");
            $this->line("Type: {$template->template_type} | Version: v{$template->version_number}");
            $this->newLine();

            try {
                // Generate sample data
                $this->line('  → Generating sample data...');
                $sampleData = SampleLeaseDataService::generate($template->template_type);

                // Create mock lease
                $mockLease = $this->createMockLease($sampleData);

                // Render template
                $this->line('  → Rendering template...');
                $html = $this->templateRenderer->render($template, $mockLease);

                if (empty(trim($html))) {
                    throw new \Exception('Template rendered empty HTML');
                }

                $this->line('  ✓ HTML rendered successfully (' . strlen($html) . ' characters)');

                // Generate PDF
                $this->line('  → Generating PDF...');
                $pdf = Pdf::loadHTML($html);
                $pdfOutput = $pdf->output();

                // Save PDF to file
                $filename = "{$template->slug}-test-" . now()->format('Y-m-d-His') . ".pdf";
                $filepath = $outputDir . '/' . $filename;
                file_put_contents($filepath, $pdfOutput);

                $this->line('  ✓ PDF generated successfully (' . number_format(strlen($pdfOutput)) . ' bytes)');
                $this->line('  📄 Saved to: ' . $filepath);

                // Show detected variables
                $this->line('  → Variables detected: ' . count($template->available_variables ?? []));
                if (!empty($template->available_variables)) {
                    $this->line('     ' . implode(', ', array_slice($template->available_variables, 0, 5)));
                    if (count($template->available_variables) > 5) {
                        $this->line('     ... and ' . (count($template->available_variables) - 5) . ' more');
                    }
                }

                $this->newLine();
                $this->info('✅ SUCCESS: Template test passed!');
                $successCount++;

            } catch (\Exception $e) {
                $this->newLine();
                $this->error('❌ FAILED: ' . $e->getMessage());
                $this->line('  Error: ' . $e->getFile() . ':' . $e->getLine());
                $failureCount++;
            }

            $this->newLine();
        }

        // Summary
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info('📊 TEST SUMMARY');
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("Total Templates Tested: {$templates->count()}");
        $this->line("✅ Successful: {$successCount}");
        $this->line("❌ Failed: {$failureCount}");
        $this->line("📁 Output Directory: {$outputDir}");
        $this->newLine();

        if ($failureCount === 0) {
            $this->info('🎉 All templates passed! PDFs have been generated successfully.');
            return self::SUCCESS;
        } else {
            $this->warn("⚠️  Some templates failed. Please review the errors above.");
            return self::FAILURE;
        }
    }

    protected function createMockLease(array $sampleData): object
    {
        $mockLease = $sampleData['lease'];
        $mockLease->tenant = $sampleData['tenant'];
        $mockLease->landlord = $sampleData['landlord'];
        $mockLease->property = $sampleData['property'];
        $mockLease->unit = $sampleData['unit'];

        return $mockLease;
    }
}
