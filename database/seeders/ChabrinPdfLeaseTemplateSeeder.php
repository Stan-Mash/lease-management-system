<?php

namespace Database\Seeders;

use App\Models\LeaseTemplate;
use Illuminate\Database\Seeder;

/**
 * Configures the three Chabrin lease templates for uploaded PDF use.
 * Runs ExactLeaseTemplateSeeder first to ensure full Blade content exists, then
 * updates those templates to source_type=uploaded_pdf. Upload PDFs via Admin → Lease Templates → PDF Upload.
 */
class ChabrinPdfLeaseTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Configuring Chabrin PDF-based lease templates...');

        $this->call(ExactLeaseTemplateSeeder::class);

        $slugs = [
            'residential-major' => 'Chabrin Residential Major Dwelling',
            'residential-micro' => 'Chabrin Residential Micro Dwelling',
            'commercial' => 'Chabrin Commercial Lease',
        ];

        foreach ($slugs as $slug => $name) {
            $template = LeaseTemplate::where('slug', $slug)->first();
            if ($template) {
                $template->update([
                    'name' => $name,
                    'description' => "CHABRIN AGENCIES lease agreement. Upload your PDF on the PDF Upload tab; generated leases will match the uploaded document. Blade content below is fallback when no PDF is uploaded.",
                    'source_type' => 'uploaded_pdf',
                    'is_default' => true,
                    'is_active' => true,
                ]);
                $this->command->info("  ✓ Configured: {$name} (ID: {$template->id})");
            }
        }

        $this->command->info('Done. Upload PDFs via Admin → Lease Templates → Edit → PDF Upload.');
    }
}
