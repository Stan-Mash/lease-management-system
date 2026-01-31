<?php

namespace Database\Seeders;

use App\Models\LeaseTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class LeaseTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Default Residential Major Template',
                'template_type' => 'residential_major',
                'source_type' => 'system_default',
                'description' => 'Default template for major residential leases',
                'is_active' => true,
                'is_default' => true,
                'view_file' => 'pdf.residential-major',
            ],
            [
                'name' => 'Default Residential Micro Template',
                'template_type' => 'residential_micro',
                'source_type' => 'system_default',
                'description' => 'Default template for micro residential leases',
                'is_active' => true,
                'is_default' => true,
                'view_file' => 'pdf.residential-micro',
            ],
            [
                'name' => 'Default Commercial Template',
                'template_type' => 'commercial',
                'source_type' => 'system_default',
                'description' => 'Default template for commercial leases',
                'is_active' => true,
                'is_default' => true,
                'view_file' => 'pdf.commercial',
            ],
        ];

        foreach ($templates as $templateData) {
            $viewFile = $templateData['view_file'];
            unset($templateData['view_file']);

            // Try to read existing Blade view content
            $viewPath = resource_path('views/' . str_replace('.', '/', $viewFile) . '.blade.php');

            if (File::exists($viewPath)) {
                $bladeContent = File::get($viewPath);
            } else {
                // Create a basic template if view doesn't exist
                $bladeContent = $this->getDefaultBladeContent($templateData['template_type']);
            }

            // Extract variables from blade content
            preg_match_all('/\{\{\s*\$([a-zA-Z0-9_>-]+)\s*\}\}/', $bladeContent, $matches);
            $availableVariables = array_unique($matches[1] ?? []);

            // Create or update template
            LeaseTemplate::updateOrCreate(
                [
                    'template_type' => $templateData['template_type'],
                    'is_default' => true,
                ],
                [
                    'name' => $templateData['name'],
                    'source_type' => $templateData['source_type'],
                    'description' => $templateData['description'],
                    'blade_content' => $bladeContent,
                    'is_active' => $templateData['is_active'],
                    'is_default' => $templateData['is_default'],
                    'available_variables' => $availableVariables,
                    'css_styles' => $this->getDefaultCssStyles(),
                    'version_number' => 1,
                    'created_by' => 1, // Assuming admin user exists
                    'updated_by' => 1,
                ],
            );

            $this->command->info("Created/Updated template: {$templateData['name']}");
        }
    }

    protected function getDefaultBladeContent(string $templateType): string
    {
        $typeTitle = strtoupper(str_replace('_', ' ', $templateType));

        return <<<BLADE
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ \$lease->reference_number }} - {$typeTitle} Lease Agreement</title>
    <style>
        @page {
            margin: 40px 50px;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0 0 10px 0;
            text-transform: uppercase;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 12px;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
        .signatures {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-box {
            display: inline-block;
            width: 45%;
            margin-top: 30px;
        }
        .qr-section {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #666;
            padding: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{$typeTitle} LEASE AGREEMENT</h1>
        <p style="margin: 5px 0;"><strong>Reference:</strong> {{ \$lease->reference_number }}</p>
        <p style="margin: 5px 0; font-size: 10px;">Generated on {{ \$today }}</p>
    </div>

    <div class="section">
        <div class="section-title">1. PARTIES TO THE AGREEMENT</div>

        <div class="info-row">
            <span class="label">LANDLORD:</span>
            <span>{{ \$landlord->name }}</span>
        </div>
        @if(\$landlord->phone)
        <div class="info-row" style="margin-left: 150px;">
            <span class="label" style="width: auto;">Phone:</span>
            <span>{{ \$landlord->phone }}</span>
        </div>
        @endif

        <div class="info-row" style="margin-top: 15px;">
            <span class="label">TENANT:</span>
            <span>{{ \$tenant->full_name }}</span>
        </div>
        <div class="info-row" style="margin-left: 150px;">
            <span class="label" style="width: auto;">ID Number:</span>
            <span>{{ \$tenant->id_number }}</span>
        </div>
        <div class="info-row" style="margin-left: 150px;">
            <span class="label" style="width: auto;">Phone:</span>
            <span>{{ \$tenant->phone }}</span>
        </div>
        @if(\$tenant->email)
        <div class="info-row" style="margin-left: 150px;">
            <span class="label" style="width: auto;">Email:</span>
            <span>{{ \$tenant->email }}</span>
        </div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">2. PROPERTY DETAILS</div>

        <div class="info-row">
            <span class="label">Property Name:</span>
            <span>{{ \$property->name }}</span>
        </div>
        @if(\$property->plot_number)
        <div class="info-row">
            <span class="label">Plot Number:</span>
            <span>{{ \$property->plot_number }}</span>
        </div>
        @endif
        @if(\$property->address)
        <div class="info-row">
            <span class="label">Address:</span>
            <span>{{ \$property->address }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="label">Unit Number:</span>
            <span>{{ \$unit->unit_number }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">3. LEASE TERMS & CONDITIONS</div>

        <div class="info-row">
            <span class="label">Commencement Date:</span>
            <span>{{ \$lease->start_date->format('d/m/Y') }}</span>
        </div>
        @if(\$lease->end_date)
        <div class="info-row">
            <span class="label">Expiry Date:</span>
            <span>{{ \$lease->end_date->format('d/m/Y') }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="label">Monthly Rent:</span>
            <span>KES {{ number_format(\$lease->monthly_rent, 2) }}</span>
        </div>
        <div class="info-row">
            <span class="label">Security Deposit:</span>
            <span>KES {{ number_format(\$lease->deposit_amount, 2) }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">4. PAYMENT TERMS</div>
        <p>The Tenant shall pay the monthly rent on or before the 5th day of each month. Rent shall be paid via M-Pesa or bank transfer to the account details provided by the Landlord.</p>
    </div>

    <div class="section">
        <div class="section-title">5. LANDLORD OBLIGATIONS</div>
        <ul style="margin: 10px 0 10px 20px;">
            <li>Ensure the premises are habitable and in good repair</li>
            <li>Provide essential services (water, electricity access)</li>
            <li>Maintain common areas and structural integrity</li>
            <li>Respect tenant's right to quiet enjoyment</li>
        </ul>
    </div>

    <div class="section">
        <div class="section-title">6. TENANT OBLIGATIONS</div>
        <ul style="margin: 10px 0 10px 20px;">
            <li>Pay rent punctually on the agreed date</li>
            <li>Maintain the premises in good condition</li>
            <li>Use the premises for residential purposes only</li>
            <li>Not sublease without written consent</li>
            <li>Comply with all local regulations and bylaws</li>
        </ul>
    </div>

    <div class="section">
        <div class="section-title">7. TERMINATION</div>
        <p>Either party may terminate this lease by giving 30 days written notice to the other party. The security deposit shall be refunded within 14 days of lease termination, subject to deductions for any damages or unpaid rent.</p>
    </div>

    <div class="signatures">
        <div class="section-title">8. SIGNATURES</div>

        <div class="signature-box">
            <p><strong>LANDLORD:</strong></p>
            <p style="margin-top: 40px; border-top: 1px solid #000; display: inline-block; width: 200px;"></p>
            <p style="font-size: 10px;">Signature & Date</p>
        </div>

        <div class="signature-box" style="float: right;">
            <p><strong>TENANT:</strong></p>
            <p style="margin-top: 40px; border-top: 1px solid #000; display: inline-block; width: 200px;"></p>
            <p style="font-size: 10px;">Signature & Date</p>
        </div>
    </div>

    <div class="qr-section">
        <img src="{{ \$qrCode }}" alt="QR Code" style="width: 100px; height: 100px;">
        <p style="font-size: 9px; margin-top: 10px;">Scan to verify lease authenticity</p>
        <p style="font-size: 8px; color: #666;">{{ \$lease->reference_number }}</p>
    </div>

    <div class="footer">
        <p>This document was generated by Chabrin Lease Management System</p>
    </div>
</body>
</html>
BLADE;
    }

    protected function getDefaultCssStyles(): array
    {
        return [
            'font_family' => 'DejaVu Sans, Arial, sans-serif',
            'font_size' => '11px',
            'line_height' => '1.6',
            'margin_top' => '40px',
            'margin_bottom' => '50px',
            'margin_left' => '50px',
            'margin_right' => '50px',
            'page_size' => 'A4',
            'orientation' => 'portrait',
        ];
    }
}
