<?php

namespace Database\Seeders;

use App\Models\LeaseTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DefaultLeaseTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding default lease templates...');

        // Define default templates
        $templates = [
            [
                'name' => 'Chabrin Residential Major Dwelling Agreement',
                'slug' => 'residential-major-default',
                'description' => 'Comprehensive formal residential lease agreement for major dwelling units (apartments, houses). Includes detailed tenant covenants, landlord responsibilities, and comprehensive terms. Features professional Chabrin branding with logo and color scheme.',
                'template_type' => 'residential_major',
                'source_type' => 'system_default',
                'view_path' => 'templates.lease-residential-major-final',
                'blade_file' => 'resources/views/templates/lease-residential-major-final.blade.php',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Chabrin Residential Micro Dwelling Agreement',
                'slug' => 'residential-micro-default',
                'description' => 'Simplified residential tenancy agreement for micro dwelling units (bedsitters, studios, single rooms). Streamlined terms with focus on essential conditions. Features professional Chabrin branding with logo and color scheme.',
                'template_type' => 'residential_micro',
                'source_type' => 'system_default',
                'view_path' => 'templates.residential-micro-final',
                'blade_file' => 'resources/views/templates/residential-micro-final.blade.php',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Chabrin Commercial Lease Agreement',
                'slug' => 'commercial-default',
                'description' => 'Professional commercial lease agreement for business premises. Includes VAT provisions, rent review clauses, business use covenants, and comprehensive commercial terms. Features modern geometric design with Chabrin branding and professional cover page.',
                'template_type' => 'commercial',
                'source_type' => 'system_default',
                'view_path' => 'templates.commercial-lease-final',
                'blade_file' => 'resources/views/templates/commercial-lease-final.blade.php',
                'is_default' => true,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $templateData) {
            $this->createTemplate($templateData);
        }

        $this->command->info('✓ Default lease templates seeded successfully!');
    }

    /**
     * Create a template with blade content loaded from file
     */
    protected function createTemplate(array $data): void
    {
        $this->command->info("Creating template: {$data['name']}");

        // Check if template already exists
        $existing = LeaseTemplate::where('slug', $data['slug'])->first();

        if ($existing) {
            $this->command->warn("  → Template '{$data['slug']}' already exists. Skipping...");

            return;
        }

        // Load blade content from file
        $bladePath = base_path($data['blade_file']);

        if (! File::exists($bladePath)) {
            $this->command->error("  ✗ Blade file not found: {$data['blade_file']}");

            return;
        }

        $bladeContent = File::get($bladePath);

        // Extract variables from blade content
        preg_match_all('/\{\{\s*\$([a-zA-Z0-9_>-]+)\s*\}\}/', $bladeContent, $matches);
        $availableVariables = array_unique($matches[1] ?? []);

        // Define required variables based on template type
        $requiredVariables = $this->getRequiredVariables($data['template_type']);

        // Create CSS styles configuration
        $cssStyles = $this->getDefaultCssStyles($data['template_type']);

        // Create the template
        LeaseTemplate::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'],
            'template_type' => $data['template_type'],
            'source_type' => $data['source_type'],
            'blade_content' => $bladeContent,
            'css_styles' => $cssStyles,
            'available_variables' => $availableVariables,
            'required_variables' => $requiredVariables,
            'is_default' => $data['is_default'],
            'is_active' => $data['is_active'],
            'version_number' => 1,
            'created_by' => 1, // System user
            'updated_by' => 1,
            'extraction_metadata' => [
                'source_file' => $data['blade_file'],
                'view_path' => $data['view_path'],
                'seeded_at' => now()->toIso8601String(),
            ],
        ]);

        $this->command->info('  ✓ Created successfully with ' . count($availableVariables) . ' variables detected');
    }

    /**
     * Get required variables for each template type
     */
    protected function getRequiredVariables(string $templateType): array
    {
        $common = [
            'lease.reference_number',
            'lease.start_date',
            'lease.end_date',
            'lease.monthly_rent',
            'lease.deposit_amount',
            'tenant.full_name',
            'tenant.id_number',
            'tenant.phone',
            'unit.unit_number',
        ];

        $typeSpecific = match ($templateType) {
            'commercial' => [
                'lease.rent_review_period',
                'lease.rent_review_percentage',
                'lease.duration_months',
                'landlord.name',
                'landlord.contact_person',
                'property.lr_number',
            ],
            'residential_major' => [
                'tenant.next_of_kin_name',
                'tenant.next_of_kin_phone',
                'property.plot_number',
                'landlord.name',
            ],
            'residential_micro' => [
                'tenant.next_of_kin_name',
                'tenant.next_of_kin_phone',
                'property.name',
            ],
            default => [],
        };

        return array_merge($common, $typeSpecific);
    }

    /**
     * Get default CSS styles for template type
     */
    protected function getDefaultCssStyles(string $templateType): array
    {
        return match ($templateType) {
            'commercial' => [
                'font_family' => 'Times New Roman, Times, serif',
                'font_size' => '10.5pt',
                'line_height' => '1.5',
                'margin_top' => '35px',
                'margin_bottom' => '45px',
                'page_size' => 'A4',
                'orientation' => 'portrait',
                'header_font_size' => '24pt',
                'color_scheme' => 'professional',
            ],
            'residential_major' => [
                'font_family' => 'Times New Roman, Times, serif',
                'font_size' => '11pt',
                'line_height' => '1.5',
                'margin_top' => '30px',
                'margin_bottom' => '40px',
                'page_size' => 'A4',
                'orientation' => 'portrait',
                'header_font_size' => '16pt',
                'color_scheme' => 'standard',
            ],
            'residential_micro' => [
                'font_family' => 'Arial, Helvetica, sans-serif',
                'font_size' => '11pt',
                'line_height' => '1.6',
                'margin_top' => '25px',
                'margin_bottom' => '35px',
                'page_size' => 'A4',
                'orientation' => 'portrait',
                'header_font_size' => '16pt',
                'color_scheme' => 'modern',
            ],
            default => [
                'font_family' => 'Arial, sans-serif',
                'font_size' => '11pt',
                'line_height' => '1.5',
                'margin_top' => '30px',
                'margin_bottom' => '40px',
                'page_size' => 'A4',
                'orientation' => 'portrait',
            ],
        };
    }
}
