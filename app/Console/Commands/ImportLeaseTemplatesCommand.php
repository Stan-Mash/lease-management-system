<?php

namespace App\Console\Commands;

use App\Models\LeaseTemplate;
use App\Services\TemplateExtractionService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ImportLeaseTemplatesCommand extends Command
{
    protected $signature = 'templates:import
                          {--force : Replace existing templates}
                          {--path= : Custom path to templates folder}';

    protected $description = 'Import PDF templates from storage and convert to editable Blade templates';

    protected TemplateExtractionService $extractionService;

    public function __construct(TemplateExtractionService $extractionService)
    {
        parent::__construct();
        $this->extractionService = $extractionService;
    }

    public function handle()
    {
        $this->info('Starting template import...');

        // Get path from option or use default
        $path = $this->option('path') ?? storage_path('app/templates/leases');

        if (! is_dir($path)) {
            $this->error("Directory not found: {$path}");

            return 1;
        }

        // Get all PDF files
        $pdfFiles = File::glob($path . '/*.pdf');

        if (empty($pdfFiles)) {
            $this->warn('No PDF files found in the directory.');

            return 0;
        }

        $this->info('Found ' . count($pdfFiles) . ' PDF file(s)');

        foreach ($pdfFiles as $pdfPath) {
            $filename = basename($pdfPath);
            $this->info("Processing: {$filename}");

            try {
                // Determine template type from filename
                $templateType = $this->determineTemplateType($filename);
                $templateName = $this->generateTemplateName($filename);

                // Check if template already exists
                $existing = LeaseTemplate::where('name', $templateName)->first();

                if ($existing && ! $this->option('force')) {
                    $this->warn("  Template '{$templateName}' already exists. Use --force to replace.");
                    continue;
                }

                // If forcing, delete existing
                if ($existing && $this->option('force')) {
                    $this->warn('  Replacing existing template...');
                    $existing->delete();
                }

                // Copy PDF to public storage for reference
                $storagePath = 'templates/leases/' . $filename;
                Storage::disk('public')->put($storagePath, file_get_contents($pdfPath));

                // Extract and create template
                $metadata = [
                    'name' => $templateName,
                    'description' => "Imported from {$filename}",
                    'template_type' => $templateType,
                ];

                $publicPath = Storage::disk('public')->path($storagePath);
                $template = $this->extractionService->extractFromPdf($publicPath, $metadata);

                // Mark as active and default for its type if no other default exists
                $hasDefault = LeaseTemplate::where('template_type', $templateType)
                    ->where('is_default', true)
                    ->where('id', '!=', $template->id)
                    ->exists();

                if (! $hasDefault) {
                    $template->update(['is_default' => true]);
                }

                $template->update(['is_active' => true]);

                $this->info("  ✓ Created template: {$templateName} (ID: {$template->id})");
                $this->info("    Type: {$templateType}");
                $this->info('    Variables found: ' . count($template->available_variables));

            } catch (Exception $e) {
                $this->error("  ✗ Failed to process {$filename}");
                $this->error('    Error: ' . $e->getMessage());
                if ($this->option('verbose')) {
                    $this->error($e->getTraceAsString());
                }
            }
        }

        $this->newLine();
        $this->info('Import completed!');
        $this->info('View templates at: ' . route('filament.admin.resources.lease-templates.index'));

        return 0;
    }

    protected function determineTemplateType(string $filename): string
    {
        $filename = strtolower($filename);

        if (str_contains($filename, 'commercial')) {
            return 'commercial';
        }

        if (str_contains($filename, 'major')) {
            return 'residential_major';
        }

        if (str_contains($filename, 'micro')) {
            return 'residential_micro';
        }

        if (str_contains($filename, 'residential')) {
            return 'residential';
        }

        // Default to residential_major
        return 'residential_major';
    }

    protected function generateTemplateName(string $filename): string
    {
        // Remove .pdf extension
        $name = preg_replace('/\.pdf$/i', '', $filename);

        // Clean up the name
        $name = str_replace(['_', '-'], ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = ucwords(strtolower($name));

        // Add "Imported" prefix to distinguish from seeded templates
        return "Imported - {$name}";
    }
}
