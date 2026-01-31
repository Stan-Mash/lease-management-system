<?php

namespace App\Console\Commands;

use App\Models\LeaseTemplate;
use App\Services\LeaseTemplateManagementService;
use Exception;
use Illuminate\Console\Command;

class ImportLeaseTemplatesFromPDF extends Command
{
    protected $signature = 'leases:import-templates {--source-path=storage/app/templates/leases}';

    protected $description = 'Import lease PDF templates and create versioned system templates';

    public function handle()
    {
        $this->info('🚀 Starting Lease Template Import System...');
        $this->newLine();

        $service = app(LeaseTemplateManagementService::class);

        // Define template configurations based on the PDFs you provided
        $templates = [
            [
                'name' => 'Residential Major - Chabrin Agencies',
                'slug' => 'residential-major-chabrin',
                'description' => 'Tenancy Lease Agreement for Major Residential Properties',
                'template_type' => 'residential_major',
                'source_type' => 'uploaded_pdf',
                'source_pdf_path' => 'lease-templates/pdfs/CHABRIN AGENCIES TENANCY LEASE AGREEMENT - MAJOR DWELLING.pdf',
                'is_default' => true,
            ],
            [
                'name' => 'Residential Micro - Chabrin Agencies',
                'slug' => 'residential-micro-chabrin',
                'description' => 'Tenancy Lease Agreement for Micro Residential Properties',
                'template_type' => 'residential_micro',
                'source_type' => 'uploaded_pdf',
                'source_pdf_path' => 'lease-templates/pdfs/CHABRIN AGENCIES TENANCY LEASE AGREEMENT - MICRO DWELLING.pdf',
                'is_default' => true,
            ],
            [
                'name' => 'Commercial - Chabrin Agencies 2022',
                'slug' => 'commercial-chabrin-2022',
                'description' => 'Commercial Lease Agreement 2022 Edition',
                'template_type' => 'commercial',
                'source_type' => 'uploaded_pdf',
                'source_pdf_path' => 'lease-templates/pdfs/COMMERCIAL LEASE - 2022 (2) (1).pdf',
                'is_default' => true,
            ],
        ];

        foreach ($templates as $templateData) {
            $this->importTemplate($templateData, $service);
        }

        $this->newLine();
        $this->info('✅ Template import complete!');
        $this->table(
            ['Type', 'Count'],
            [
                ['Total Templates', LeaseTemplate::count()],
                ['Total Versions', \App\Models\LeaseTemplateVersion::count()],
            ],
        );
    }

    private function importTemplate(array $data, LeaseTemplateManagementService $service)
    {
        $this->line("📄 Processing: {$data['name']}");

        // Check if template already exists
        $existing = LeaseTemplate::where('slug', $data['slug'])->first();

        if ($existing) {
            $this->comment("   → Template already exists (ID: {$existing->id}, v{$existing->version_number})");

            return;
        }

        try {
            // Create placeholder blade content pointing to PDF
            $bladeContent = $this->createBladeFromPDF($data);

            // Merge blade content with template data
            $data['blade_content'] = $bladeContent;
            $data['is_active'] = true;
            $data['created_by'] = 1; // System admin

            // Create template with versioning
            $template = $service->createTemplate(
                $data,
                "Initial template from PDF: {$data['source_pdf_path']}",
            );

            $this->info("   ✓ Created template v{$template->version_number} (ID: {$template->id})");

        } catch (Exception $e) {
            $this->error("   ✗ Failed: {$e->getMessage()}");
        }
    }

    /**
     * Create Blade template content from PDF metadata
     * This serves as reference; actual rendering uses the Blade templates
     */
    private function createBladeFromPDF(array $data): string
    {
        $templateType = $data['template_type'];

        // Return reference to the existing Blade templates
        // In production, these would be custom Blade templates that exactly match the PDFs
        $bladeContent = match ($templateType) {
            'residential_major' => $this->getResidentialMajorTemplate(),
            'residential_micro' => $this->getResidentialMicroTemplate(),
            'commercial' => $this->getCommercialTemplate(),
        };

        return $bladeContent;
    }

    private function getResidentialMajorTemplate(): string
    {
        return <<<'BLADE'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tenancy Lease Agreement - {{ $lease->reference_number }}</title>
    <style>
        @page {
            margin: 40px 50px;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            border-bottom: 3px solid #FFD700;
        }
        .field-line {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 150px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- This template renders based on the Residential Major PDF structure -->
    <div class="header">
        <h3>CHABRIN AGENCIES LTD</h3>
        <p>TENANCY LEASE AGREEMENT</p>
    </div>

    <div class="title">TENANCY LEASE AGREEMENT</div>

    <!-- Parties Section -->
    <p><strong>BETWEEN</strong></p>
    <p>
        1. <span class="field-line">{{ $landlord->name ?? '' }}</span> c/o<br>
        <strong>MANAGING AGENT: CHABRIN AGENCIES LTD</strong><br>
        P O BOX 16659-00620<br>
        NAIROBI
    </p>

    <p><strong>AND</strong></p>
    <p>
        <span class="field-line">{{ $tenant->full_name ?? $tenant->name ?? '' }}</span><br>
        ID No: <span class="field-line">{{ $tenant->id_number ?? '' }}</span>
    </p>

    <!-- Property Description -->
    <p><strong>IN RESPECT OF RESIDENTIAL PREMISES DESIGNED AS:</strong></p>
    <div class="field-row">
        <strong>PLOT NO:</strong> <span class="field-line">{{ $property->plot_number ?? '' }}</span>
        <strong>Flat no:</strong> <span class="field-line">{{ $unit->unit_number ?? '' }}</span>
    </div>

    <!-- Date -->
    <p>
        This tenancy agreement is made on the <span class="field-line">{{ $lease->created_at ? $lease->created_at->format('d / m / Y') : '___/___/___' }}</span>
    </p>

    <!-- Terms -->
    <p><strong>HEREBY AGREE AS FOLLOWS:</strong></p>

    <!-- Schedule -->
    <div class="schedule-title">THE SCHEDULE</div>
    <ol type="a">
        <li>The date of commencement of the lease is <span class="field-line">{{ $lease->start_date ? $lease->start_date->format('d/m/Y') : '............' }}</span></li>
        <li>The term of tenancy is periodic tenancy.</li>
        <li>The monthly rent is Kshs <span class="field-line">{{ number_format($lease->monthly_rent, 2) }}</span></li>
        <li>The rent shall be reviewed after each calendar year to the market rates or to such a reasonable figure.</li>
        <li>Deposit: Kshs <span class="field-line">{{ number_format($lease->deposit_amount, 2) }}</span></li>
    </ol>

    <!-- Signatures -->
    <div class="signatures" style="margin-top: 50px;">
        <table width="100%">
            <tr>
                <td width="50%" style="text-align: center;">
                    <p>TENANT<br>
                    <span class="field-line">{{ $tenant->full_name ?? $tenant->name ?? '' }}</span></p>
                </td>
                <td width="50%" style="text-align: center;">
                    <p>FOR AND ON BEHALF OF<br>
                    LANDLORD/MANAGING AGENT<br>
                    <span class="field-line">CHABRIN AGENCIES LTD</span></p>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
BLADE;
    }

    private function getResidentialMicroTemplate(): string
    {
        return <<<'BLADE'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tenancy Lease Agreement - {{ $lease->reference_number }}</title>
    <style>
        @page { margin: 40px 50px; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }
        .field-line { border-bottom: 1px solid #000; display: inline-block; min-width: 150px; }
        .title { font-size: 16px; font-weight: bold; text-align: center; margin: 20px 0; }
    </style>
</head>
<body>
    <!-- Residential Micro Template -->
    <div class="title">TENANCY LEASE AGREEMENT - MICRO DWELLING</div>
    
    <p><strong>BETWEEN</strong></p>
    <p>LANDLORD: <span class="field-line">{{ $landlord->name ?? '' }}</span></p>
    
    <p><strong>AND</strong></p>
    <p>TENANT: <span class="field-line">{{ $tenant->full_name ?? $tenant->name ?? '' }}</span></p>
    
    <p>Property: <span class="field-line">{{ $property->name ?? '' }}</span></p>
    <p>Unit: <span class="field-line">{{ $unit->unit_number ?? '' }}</span></p>
    <p>Monthly Rent: Kshs <span class="field-line">{{ number_format($lease->monthly_rent, 2) }}</span></p>
    <p>Deposit: Kshs <span class="field-line">{{ number_format($lease->deposit_amount, 2) }}</span></p>
    
    <div style="margin-top: 50px;">
        <table width="100%">
            <tr>
                <td width="50%" style="text-align: center;">TENANT SIGNATURE</td>
                <td width="50%" style="text-align: center;">LANDLORD/AGENT</td>
            </tr>
        </table>
    </div>
</body>
</html>
BLADE;
    }

    private function getCommercialTemplate(): string
    {
        return <<<'BLADE'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Commercial Lease - {{ $lease->reference_number }}</title>
    <style>
        @page { margin: 60px 50px; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }
        .field-value { border-bottom: 1px solid #000; display: inline-block; min-width: 200px; }
        .cover-title { font-size: 18px; font-weight: bold; text-align: center; margin: 100px 0; }
    </style>
</head>
<body>
    <!-- Commercial Lease Template -->
    <div class="cover-title">COMMERCIAL LEASE AGREEMENT</div>
    
    <h3>PARTICULARS</h3>
    <p>Date: <span class="field-value">{{ $lease->created_at ? $lease->created_at->format('d/m/Y') : '___/___/___' }}</span></p>
    
    <h3>PARTIES</h3>
    <p>LESSOR: <span class="field-value">{{ $landlord->name ?? '' }}</span></p>
    <p>LESSEE: <span class="field-value">{{ $tenant->full_name ?? $tenant->name ?? '' }}</span></p>
    <p>ID No: <span class="field-value">{{ $tenant->id_number ?? '' }}</span></p>
    
    <h3>PROPERTY</h3>
    <p>Property: <span class="field-value">{{ $property->name ?? '' }}</span></p>
    <p>Unit: <span class="field-value">{{ $unit->unit_number ?? '' }}</span></p>
    
    <h3>FINANCIAL TERMS</h3>
    <p>Term: <span class="field-value">{{ $lease->lease_term_months ?? '___' }} months</span></p>
    <p>Monthly Rent: Kshs <span class="field-value">{{ number_format($lease->monthly_rent, 2) }}</span></p>
    <p>Deposit: Kshs <span class="field-value">{{ number_format($lease->deposit_amount, 2) }}</span></p>
    
    <div style="margin-top: 100px;">
        <table width="100%">
            <tr>
                <td width="50%" style="text-align: center;">
                    LESSEE SIGNATURE<br><br>
                    _______________<br>
                    {{ $tenant->full_name ?? $tenant->name ?? '' }}
                </td>
                <td width="50%" style="text-align: center;">
                    LESSOR SIGNATURE<br><br>
                    _______________<br>
                    {{ $landlord->name ?? '' }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
BLADE;
    }
}
