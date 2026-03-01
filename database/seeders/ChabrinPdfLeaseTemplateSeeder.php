<?php

namespace Database\Seeders;

use App\Models\LeaseTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the three Chabrin lease templates (Residential Major, Residential Micro, Commercial)
 * configured for uploaded PDF use. Upload your PDFs via the admin Lease Templates → Edit → PDF Upload tab.
 */
class ChabrinPdfLeaseTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Chabrin PDF-based lease templates...');

        $userId = User::first()?->id;
        if (! $userId) {
            throw new \RuntimeException('No users in database. Run RolesAndPermissionsSeeder and SuperUserSeeder first.');
        }

        $templates = [
            [
                'name' => 'Chabrin Residential Major Dwelling',
                'slug' => 'chabrin-residential-major-pdf',
                'description' => 'CHABRIN AGENCIES Tenancy Lease Agreement for Major Residential Properties. Upload your PDF on the PDF Upload tab; output will match the uploaded document.',
                'template_type' => 'residential_major',
            ],
            [
                'name' => 'Chabrin Residential Micro Dwelling',
                'slug' => 'chabrin-residential-micro-pdf',
                'description' => 'CHABRIN AGENCIES Tenancy Agreement for Micro Residential Properties. Upload your PDF on the PDF Upload tab; output will match the uploaded document.',
                'template_type' => 'residential_micro',
            ],
            [
                'name' => 'Chabrin Commercial Lease',
                'slug' => 'chabrin-commercial-pdf',
                'description' => 'CHABRIN AGENCIES Commercial Lease Agreement. Upload your PDF on the PDF Upload tab; output will match the uploaded document.',
                'template_type' => 'commercial',
            ],
        ];

        foreach ($templates as $data) {
            $this->createOrUpdate($data, $userId);
        }

        $this->command->info('✓ Chabrin PDF lease templates seeded. Upload PDFs via Admin → Lease Templates → Edit → PDF Upload.');
    }

    private function createOrUpdate(array $data, int $userId): void
    {
        $this->command->info("  Processing: {$data['name']}");

        LeaseTemplate::where('template_type', $data['template_type'])
            ->where('is_default', true)
            ->update(['is_default' => false]);

        $template = LeaseTemplate::firstOrNew(['slug' => $data['slug']]);

        if ($template->exists) {
            $template->update([
                'name' => $data['name'],
                'description' => $data['description'],
                'source_type' => 'uploaded_pdf',
                'is_default' => true,
                'is_active' => true,
                'updated_by' => $userId,
            ]);
            $this->command->info("    ✓ Updated (ID: {$template->id})");
        } else {
            $template->fill([
                'name' => $data['name'],
                'description' => $data['description'],
                'template_type' => $data['template_type'],
                'source_type' => 'uploaded_pdf',
                'blade_content' => $this->minimalBladeContent($data['template_type']),
                'available_variables' => ['lease', 'tenant', 'unit', 'property', 'client', 'qrCode'],
                'required_variables' => [],
                'is_default' => true,
                'is_active' => true,
                'version_number' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
                'extraction_metadata' => [
                    'seeded_by' => 'ChabrinPdfLeaseTemplateSeeder',
                    'seeded_at' => now()->toIso8601String(),
                ],
            ]);
            $template->save();
            $this->command->info("    ✓ Created (ID: {$template->id})");
        }
    }

    private function minimalBladeContent(string $templateType): string
    {
        $title = match ($templateType) {
            'residential_major' => 'RESIDENTIAL TENANCY LEASE AGREEMENT',
            'residential_micro' => 'RESIDENTIAL TENANCY AGREEMENT',
            'commercial' => 'COMMERCIAL LEASE AGREEMENT',
            default => 'Lease Agreement',
        };

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>{{ \$lease->reference_number }}</title></head>
<body>
<p><strong>{$title}</strong></p>
<p>Reference: {{ \$lease->reference_number }}</p>
<p>Tenant: {{ \$tenant->names ?? '' }}</p>
<p>Property: {{ \$property->property_name ?? '' }}</p>
<p>Unit: {{ \$unit->unit_number ?? '' }}</p>
<p>Rent: Kshs {{ number_format(\$lease->monthly_rent ?? 0, 2) }}</p>
<p>Period: {{ \$lease->start_date?->format('d/m/Y') }} – {{ \$lease->end_date?->format('d/m/Y') }}</p>
<p><em>When a PDF is uploaded on the PDF Upload tab, this Blade content is not used — the uploaded PDF is the output.</em></p>
</body>
</html>
HTML;
    }
}
