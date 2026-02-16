<?php

namespace App\Filament\Resources\LeaseTemplateResource\Pages;

use App\Filament\Resources\LeaseTemplateResource;
use App\Services\TemplateExtractionService;
use Exception;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateLeaseTemplate extends CreateRecord
{
    protected static string $resource = LeaseTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle PDF upload and extraction
        if (! empty($data['source_pdf_path']) && $data['source_type'] === 'uploaded_pdf') {
            $pdfPath = Storage::disk('public')->path($data['source_pdf_path']);

            // Use extraction service to convert PDF to Blade
            $extractionService = app(TemplateExtractionService::class);

            try {
                // Extract content from PDF
                $pdf = (new \Smalot\PdfParser\Parser)->parseFile($pdfPath);
                $textContent = $pdf->getText();

                // Convert to Blade
                $bladeContent = $this->convertTextToBlade($textContent, $data['template_type']);
                $data['blade_content'] = $bladeContent;

                // Extract variables
                preg_match_all('/\{\{\s*\$([a-zA-Z0-9_>-]+)\s*\}\}/', $bladeContent, $matches);
                $data['available_variables'] = array_unique($matches[1] ?? []);

                // Extract PDF metadata
                $data['extraction_metadata'] = [
                    'page_count' => count($pdf->getPages()),
                    'title' => $pdf->getDetails()['Title'] ?? null,
                    'author' => $pdf->getDetails()['Author'] ?? null,
                    'extracted_at' => now()->toISOString(),
                ];
            } catch (Exception $e) {
                // If extraction fails, provide empty template
                $data['blade_content'] = $this->getEmptyTemplate($data['template_type']);
                $data['extraction_metadata'] = [
                    'extraction_failed' => true,
                    'error' => $e->getMessage(),
                ];
            }
        } elseif ($data['source_type'] === 'custom_blade' && empty($data['blade_content'])) {
            // Provide starter template for custom Blade
            $data['blade_content'] = $this->getEmptyTemplate($data['template_type']);
        }

        // Set initial version
        $data['version_number'] = 1;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        // If marked as default, unset other defaults for this type
        if (! empty($data['is_default'])) {
            \App\Models\LeaseTemplate::where('template_type', $data['template_type'])
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        return $data;
    }

    protected function convertTextToBlade(string $text, string $templateType): string
    {
        $blade = $this->wrapInHtmlStructure($text);

        // Replace common patterns with Blade variables
        $replacements = [
            // Tenant info
            '/Tenant[:\s]+([A-Z][a-zA-Z\s]+)/' => 'Tenant: {{ $tenant->names }}',
            '/ID\s*No[:\s]+(\d+)/' => 'ID No: {{ $tenant->national_id }}',
            '/Tel[:\s]+([\d\-\+\s]+)/' => 'Tel: {{ $tenant->mobile_number }}',
            '/Email[:\s]+([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/' => 'Email: {{ $tenant->email_address }}',

            // Client info
            '/Landlord[:\s]+([A-Z][a-zA-Z\s]+)/' => 'Client: {{ $client->names }}',

            // Property info
            '/Plot\s*No[:\s]+([\w\-\/]+)/' => 'Plot No: {{ $property->plot_number }}',
            '/Flat\s*no[:\s]+([\w\-]+)/i' => 'Flat no: {{ $unit->unit_number }}',

            // Financial
            '/Kshs?[\s\.]+([\d,]+\.?\d*)/' => 'Kshs {{ number_format($lease->monthly_rent, 2) }}',
            '/rent[:\s]+Kshs?[\s\.]+([\d,]+)/i' => 'rent: Kshs {{ number_format($lease->monthly_rent, 2) }}',
            '/deposit[:\s]+Kshs?[\s\.]+([\d,]+)/i' => 'deposit: Kshs {{ number_format($lease->deposit_amount, 2) }}',

            // Dates
            '/\d{1,2}\/\d{1,2}\/\d{2,4}/' => '{{ $lease->start_date->format(\'d/m/Y\') }}',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $blade = preg_replace($pattern, $replacement, $blade);
        }

        return $blade;
    }

    protected function wrapInHtmlStructure(string $content): string
    {
        $escapedContent = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        return <<<BLADE
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ \$lease->reference_number }} - Lease Agreement</title>
    <style>
        @page { margin: 40px 50px; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .content {
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ \$lease->lease_type === 'commercial' ? 'COMMERCIAL' : 'RESIDENTIAL' }} LEASE AGREEMENT</h2>
        <p>Reference: {{ \$lease->reference_number }}</p>
    </div>
    <div class="content">
{$escapedContent}
    </div>
</body>
</html>
BLADE;
    }

    protected function getEmptyTemplate(string $templateType): string
    {
        $typeTitle = strtoupper(str_replace('_', ' ', $templateType));

        return <<<BLADE
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ \$lease->reference_number }} - {$typeTitle} Lease Agreement</title>
    <style>
        @page { margin: 40px 50px; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{$typeTitle} LEASE AGREEMENT</h2>
        <p>Reference: {{ \$lease->reference_number }}</p>
    </div>

    <div class="section">
        <div class="section-title">PARTIES</div>
        <p><strong>Client:</strong> {{ \$client->names }}</p>
        <p><strong>Tenant:</strong> {{ \$tenant->names }} (ID: {{ \$tenant->national_id }})</p>
    </div>

    <div class="section">
        <div class="section-title">PROPERTY DETAILS</div>
        <p><strong>Property:</strong> {{ \$property->property_name }}</p>
        <p><strong>Plot Number:</strong> {{ \$property->plot_number }}</p>
        <p><strong>Unit:</strong> {{ \$unit->unit_number }}</p>
    </div>

    <div class="section">
        <div class="section-title">LEASE TERMS</div>
        <p><strong>Start Date:</strong> {{ \$lease->start_date->format('d/m/Y') }}</p>
        <p><strong>End Date:</strong> {{ \$lease->end_date->format('d/m/Y') }}</p>
        <p><strong>Monthly Rent:</strong> Kshs {{ number_format(\$lease->monthly_rent, 2) }}</p>
        <p><strong>Deposit:</strong> Kshs {{ number_format(\$lease->deposit_amount, 2) }}</p>
    </div>

    <div class="section">
        <div class="section-title">SIGNATURES</div>
        <p>Client: ___________________ Date: ___________</p>
        <p>Tenant: ___________________ Date: ___________</p>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <img src="{{ \$qrCode }}" alt="QR Code" style="width: 100px; height: 100px;">
        <p style="font-size: 9px;">Scan to verify lease authenticity</p>
    </div>
</body>
</html>
BLADE;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
