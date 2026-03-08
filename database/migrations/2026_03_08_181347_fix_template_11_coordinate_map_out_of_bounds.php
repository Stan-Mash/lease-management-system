<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Fix out-of-bounds PDF coordinates in Commercial Lease template (ID 11).
     *
     * Three fields had PDF-point values instead of millimetres, causing FPDF's
     * auto-page-break to fire and insert 2 extra blank pages into every generated lease:
     *   - tenant_id_number:        x=272, y=366  (page is only 279.4 mm tall)
     *   - property_name:           x=209, y=291  (exceeds page height)
     *   - grant_of_lease_duration: x=246, y=387  (way out of bounds; field is also
     *                                              hardcoded in the PDF text, so remove)
     *
     * Correct mm coordinates are from the v6 DataTm + underline-rect extraction.
     */
    public function up(): void
    {
        $template = \App\Models\LeaseTemplate::find(11);

        if (! $template) {
            // Template not imported on this environment — skip safely.
            return;
        }

        $coords = $template->pdf_coordinate_map;

        if (! is_array($coords)) {
            return;
        }

        // Fix tenant_id_number — correct position for "or Company registration no." blank
        $coords['tenant_id_number'] = [
            'page'  => 2,
            'x'     => 130.5,
            'y'     => 97.3,
            'size'  => 12,
            'color' => 'FF0000',
        ];

        // Fix property_name — correct position for "Designed as" blank
        $coords['property_name'] = [
            'page'  => 2,
            'x'     => 100.4,
            'y'     => 133.7,
            'size'  => 12,
            'color' => 'FF0000',
        ];

        // Remove grant_of_lease_duration — the Commercial Lease PDF hardcodes this text;
        // overlaying it would duplicate copy and the old coords were out of bounds anyway.
        unset($coords['grant_of_lease_duration']);

        $template->pdf_coordinate_map = $coords;
        $template->save();
    }

    public function down(): void
    {
        // Restoring the broken out-of-bounds coordinates intentionally is not useful.
    }
};
