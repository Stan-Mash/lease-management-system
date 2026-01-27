<?php

namespace App\Services;

use App\Models\LeaseTemplate;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use Illuminate\Support\Str;

/**
 * Extracts content from uploaded PDF files and converts to editable Blade templates
 */
class TemplateExtractionService
{
    protected PdfParser $pdfParser;

    public function __construct()
    {
        $this->pdfParser = new PdfParser();
    }

    /**
     * Extract content from uploaded PDF and create template
     *
     * @param string $pdfPath Path to uploaded PDF
     * @param array $metadata Template metadata (name, type, etc.)
     * @return LeaseTemplate
     */
    public function extractFromPdf(string $pdfPath, array $metadata): LeaseTemplate
    {
        // Parse PDF
        $pdf = $this->pdfParser->parseFile($pdfPath);

        // Extract text content
        $textContent = $pdf->getText();

        // Extract metadata
        $pdfMetadata = [
            'page_count' => count($pdf->getPages()),
            'title' => $pdf->getDetails()['Title'] ?? null,
            'author' => $pdf->getDetails()['Author'] ?? null,
            'created_date' => $pdf->getDetails()['CreationDate'] ?? null,
        ];

        // Convert to Blade template
        $bladeContent = $this->convertTextToBlade($textContent, $metadata['template_type']);

        // Extract CSS/styling from PDF (basic detection)
        $cssStyles = $this->extractStyling($pdf);

        // Identify variable placeholders
        $variables = $this->identifyVariablePlaceholders($textContent);

        // Create template
        return LeaseTemplate::create([
            'name' => $metadata['name'],
            'description' => $metadata['description'] ?? null,
            'template_type' => $metadata['template_type'],
            'source_type' => 'uploaded_pdf',
            'blade_content' => $bladeContent,
            'css_styles' => $cssStyles,
            'source_pdf_path' => $pdfPath,
            'extraction_metadata' => $pdfMetadata,
            'available_variables' => $variables,
            'created_by' => auth()->id(),
            'version_number' => 1,
        ]);
    }

    /**
     * Convert plain text to Blade template with placeholders
     */
    protected function convertTextToBlade(string $text, string $templateType): string
    {
        $blade = $this->wrapInHtmlStructure($text);

        // Replace common patterns with Blade variables
        $replacements = [
            // Tenant info
            '/Tenant[:\s]+([A-Z][a-zA-Z\s]+)/' => 'Tenant: {{ $tenant->full_name }}',
            '/ID\s*No[:\s]+(\d+)/' => 'ID No: {{ $tenant->id_number }}',
            '/Tel[:\s]+([\d\-\+\s]+)/' => 'Tel: {{ $tenant->phone }}',

            // Landlord info
            '/Landlord[:\s]+([A-Z][a-zA-Z\s]+)/' => 'Landlord: {{ $landlord->name }}',

            // Property info
            '/Plot\s*No[:\s]+([\w\-\/]+)/' => 'Plot No: {{ $property->plot_number }}',
            '/Flat\s*no[:\s]+([\w\-]+)/i' => 'Flat no: {{ $unit->unit_number }}',

            // Financial
            '/Kshs?[\s\.]+([\d,]+\.?\d*)/' => 'Kshs {{ number_format($lease->monthly_rent, 2) }}',
            '/rent[:\s]+Kshs?[\s\.]+([\d,]+)/i' => 'rent: Kshs {{ number_format($lease->monthly_rent, 2) }}',

            // Dates
            '/\d{1,2}\/\d{1,2}\/\d{2,4}/' => '{{ $lease->start_date->format(\'d/m/Y\') }}',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $blade = preg_replace($pattern, $replacement, $blade);
        }

        return $blade;
    }

    /**
     * Wrap content in proper HTML/Blade structure
     */
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
        /* Additional styles will be inserted here */
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

    /**
     * Extract basic styling information from PDF
     */
    protected function extractStyling($pdf): array
    {
        // This is a simplified version - PDF styling extraction is complex
        return [
            'font_family' => 'Arial, sans-serif',
            'font_size' => '11px',
            'line_height' => '1.4',
            'margin_top' => '40px',
            'margin_bottom' => '50px',
            'page_size' => 'A4',
            'orientation' => 'portrait',
        ];
    }

    /**
     * Identify potential variable placeholders in text
     */
    protected function identifyVariablePlaceholders(string $text): array
    {
        $variables = [];

        // Pattern matching for common lease fields
        if (preg_match('/tenant/i', $text)) {
            $variables[] = 'tenant.full_name';
            $variables[] = 'tenant.id_number';
            $variables[] = 'tenant.phone';
        }

        if (preg_match('/landlord/i', $text)) {
            $variables[] = 'landlord.name';
        }

        if (preg_match('/rent|kshs/i', $text)) {
            $variables[] = 'lease.monthly_rent';
            $variables[] = 'lease.deposit_amount';
        }

        if (preg_match('/plot|property|flat|unit/i', $text)) {
            $variables[] = 'property.plot_number';
            $variables[] = 'unit.unit_number';
        }

        if (preg_match('/date|commencement|start/i', $text)) {
            $variables[] = 'lease.start_date';
            $variables[] = 'lease.end_date';
        }

        return array_unique($variables);
    }
}
