<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Add witness_signature and advocate_signature coordinates to lease templates
 * that already have a pdf_coordinate_map but are missing these entries.
 * This ensures the witness signature and advocate signature get stamped
 * onto the execution page of generated PDFs.
 */
return new class extends Migration
{
    public function up(): void
    {
        $templates = \App\Models\LeaseTemplate::whereNotNull('source_pdf_path')
            ->where('source_pdf_path', '!=', '')
            ->get();

        foreach ($templates as $template) {
            $coords = $template->pdf_coordinate_map;

            if (! is_array($coords) || empty($coords)) {
                continue;
            }

            $changed = false;

            // Add witness_signature if missing — positioned below the lessee section
            if (! isset($coords['witness_signature'])) {
                // Use the same page as tenant_signature if available
                $sigPage = $coords['tenant_signature']['page'] ?? $coords['manager_signature']['page'] ?? 2;
                $coords['witness_signature'] = [
                    'page'   => $sigPage,
                    'x'      => 20,
                    'y'      => 260,
                    'width'  => 50,
                    'height' => 20,
                ];
                $changed = true;
            }

            // Add advocate_signature if missing
            if (! isset($coords['advocate_signature'])) {
                $sigPage = $coords['tenant_signature']['page'] ?? $coords['manager_signature']['page'] ?? 2;
                $coords['advocate_signature'] = [
                    'page'   => $sigPage,
                    'x'      => 20,
                    'y'      => 280,
                    'width'  => 45,
                    'height' => 18,
                    'anchor' => 'beside',
                ];
                $changed = true;
            }

            if ($changed) {
                $template->pdf_coordinate_map = $coords;
                $template->save();
            }
        }
    }

    public function down(): void
    {
        // Removing coordinates is not destructive — they're simply ignored if no signature exists
    }
};
