<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\LeaseTemplate;
use App\Models\LeaseTemplateVersion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

/**
 * TemplateRenderService
 * 
 * Renders lease documents using versioned templates
 * Ensures consistency and immutability of rendered leases
 */
class TemplateRenderService
{
    /**
     * Render a lease using a specific template version
     */
    public function renderLease(Lease $lease, ?LeaseTemplate $template = null): string
    {
        $lease->load(['tenant', 'unit', 'property', 'landlord']);

        // Determine which template to use
        if (!$template && $lease->lease_template_id) {
            $template = LeaseTemplate::findOrFail($lease->lease_template_id);
        }

        // If no template assigned, use default for lease type
        if (!$template) {
            $template = LeaseTemplate::where('template_type', $lease->lease_type)
                ->where('is_active', true)
                ->where('is_default', true)
                ->firstOrFail();
        }

        // Get the active version
        $version = $template->versions()
            ->orderByDesc('version_number')
            ->firstOrFail();

        // Store version used for audit trail
        if (!$lease->template_version_used) {
            $lease->update(['template_version_used' => $version->version_number]);
        }

        return $this->compileTemplate($version, $lease);
    }

    /**
     * Render a template version with provided data
     */
    public function renderVersion(LeaseTemplateVersion $version, Lease $lease): string
    {
        $lease->load(['tenant', 'unit', 'property', 'landlord']);
        return $this->compileTemplate($version, $lease);
    }

    /**
     * Compile Blade template with lease data
     */
    private function compileTemplate(LeaseTemplateVersion $version, Lease $lease): string
    {
        try {
            // Prepare data for template
            $data = $this->prepareTemplateData($lease);

            // Create a temporary view from blade content
            $viewPath = 'template-' . $version->id . '-' . time();

            // Register view dynamically
            View::addNamespace('dynamic', storage_path('views'));

            // Save blade content to temporary view file
            $this->createTemporaryView($viewPath, $version->blade_content);

            // Render the view
            $html = view("dynamic::{$viewPath}", $data)->render();

            // Cleanup
            $this->deleteTemporaryView($viewPath);

            Log::info('Template rendered successfully', [
                'lease_id' => $lease->id,
                'template_version_id' => $version->id,
                'version_number' => $version->version_number,
            ]);

            return $html;
        } catch (\Exception $e) {
            Log::error('Failed to render template', [
                'lease_id' => $lease->id,
                'template_version_id' => $version->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Prepare all data needed for template rendering
     */
    private function prepareTemplateData(Lease $lease): array
    {
        return [
            'lease' => $lease,
            'tenant' => $lease->tenant,
            'landlord' => $lease->landlord,
            'property' => $lease->property,
            'unit' => $lease->unit,
            'today' => now()->format('d/m/Y'),
            'current_date' => now()->format('F j, Y'),
            'month_year' => now()->format('F Y'),
            'formatted_rent' => number_format($lease->monthly_rent, 2),
            'formatted_deposit' => number_format($lease->deposit_amount, 2),
        ];
    }

    /**
     * Create a temporary view file for Blade compilation
     */
    private function createTemporaryView(string $viewName, string $bladeContent): void
    {
        $viewsPath = storage_path('views');

        if (!is_dir($viewsPath)) {
            mkdir($viewsPath, 0755, true);
        }

        $fileName = $viewsPath . '/' . str_replace('.', '/', $viewName) . '.blade.php';
        $dir = dirname($fileName);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fileName, $bladeContent);
    }

    /**
     * Delete temporary view file
     */
    private function deleteTemporaryView(string $viewName): void
    {
        $fileName = storage_path('views') . '/' . str_replace('.', '/', $viewName) . '.blade.php';

        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    /**
     * Validate template before rendering
     */
    public function validateBeforeRender(LeaseTemplate $template, Lease $lease): array
    {
        $errors = [];

        // Check template is active
        if (!$template->is_active) {
            $errors[] = 'Template is inactive';
        }

        // Check template type matches lease type
        if ($template->template_type !== $lease->lease_type) {
            $errors[] = "Template type ({$template->template_type}) does not match lease type ({$lease->lease_type})";
        }

        // Check required variables
        if ($template->required_variables) {
            $availableVariables = $template->extractVariables();
            foreach ($template->required_variables as $required) {
                if (!in_array($required, $availableVariables)) {
                    $errors[] = "Required template variable not found: \${$required}";
                }
            }
        }

        // Check lease has required data
        $requiredLeaseFields = ['tenant_id', 'property_id', 'unit_id', 'landlord_id'];
        foreach ($requiredLeaseFields as $field) {
            if (empty($lease->{$field})) {
                $errors[] = "Lease missing required field: {$field}";
            }
        }

        return $errors;
    }

    /**
     * Get template preview with sample data
     */
    public function getTemplatePreview(LeaseTemplate $template, LeaseTemplateVersion $version): string
    {
        // Create sample lease data
        $sampleLease = new Lease([
            'reference_number' => 'SAMPLE-2026-0001',
            'monthly_rent' => 50000,
            'deposit_amount' => 50000,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'lease_type' => $template->template_type,
        ]);

        $sampleLease->tenant = new \App\Models\Tenant([
            'full_name' => 'John Doe',
            'id_number' => '12345678',
            'phone' => '+254700000000',
            'email' => 'john@example.com',
        ]);

        $sampleLease->landlord = new \App\Models\Landlord([
            'name' => 'Jane Smith',
            'po_box' => '12345',
        ]);

        $sampleLease->property = new \App\Models\Property([
            'name' => 'Sample Property',
            'plot_number' => 'PLOT-001',
        ]);

        $sampleLease->unit = new \App\Models\Unit([
            'unit_number' => 'UNIT-001',
        ]);

        return $this->renderVersion($version, $sampleLease);
    }
}
