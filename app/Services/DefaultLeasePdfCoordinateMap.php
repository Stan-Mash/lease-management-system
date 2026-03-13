<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Default PDF coordinate map for the standard "Particulars" layout.
 * Coordinates in mm; font size 12 for page-1 fields, 9 for signing-page fields.
 * Use with templates:apply-default-coordinates to fill templates that have a PDF but no map.
 *
 * SIGNING PAGE LAYOUT (two columns, Lessor left / Lessee right):
 *   Box 1 (left top)    — Lessor: name, ID, signature, date
 *   Box 2 (left mid)    — Lessor Witness: name, ID, signature, date
 *   Box 3 (left bot)    — Lessor Advocate: name, firm, LSK, signature, stamp, date
 *   Box 4 (right top)   — Lessee: name, ID, signature, date
 *   Box 5 (right mid)   — Lessee Witness: name, ID, signature, date
 *   Box 6 (right bot)   — Lessee Advocate: name, firm, LSK, signature, stamp, date
 *
 * Coordinates are INITIAL ESTIMATES — calibrate visually with:
 *   php artisan leases:preview-coordinates {templateId}
 */
class DefaultLeasePdfCoordinateMap
{
    /**
     * Full default map: page-1 particulars + signing-page fields (page 2 by default).
     * Override signing page number via signingPage($n) for templates with more pages.
     */
    public static function particularsPage1(): array
    {
        return array_merge(
            self::page1Fields(),
            self::legacySignaturePlaceholders(),
            self::signingPage(2),
        );
    }

    /**
     * Page 1 "Particulars": date, lessor, lessee, building, term, financials, reference.
     *
     * @return array<string, array{page: int, x: float, y: float, size: int, width: float, align: string}>
     */
    public static function page1Fields(): array
    {
        return [
            // Date: "dated the __ day on the month of __ in the year __"
            'lease_date_day'   => ['page' => 1, 'x' => 25, 'y' => 45, 'size' => 12, 'width' => 12, 'align' => 'L'],
            'lease_date_month' => ['page' => 1, 'x' => 42, 'y' => 45, 'size' => 12, 'width' => 28, 'align' => 'L'],
            'lease_date_year'  => ['page' => 1, 'x' => 75, 'y' => 45, 'size' => 12, 'width' => 18, 'align' => 'L'],

            // Lessor
            'landlord_name'   => ['page' => 1, 'x' => 25, 'y' => 58, 'size' => 12, 'width' => 75, 'align' => 'L'],
            'landlord_po_box' => ['page' => 1, 'x' => 105, 'y' => 58, 'size' => 12, 'width' => 25, 'align' => 'L'],

            // Lessee
            'tenant_name'      => ['page' => 1, 'x' => 25, 'y' => 72, 'size' => 12, 'width' => 70, 'align' => 'L'],
            'tenant_id_number' => ['page' => 1, 'x' => 100, 'y' => 72, 'size' => 12, 'width' => 35, 'align' => 'L'],
            'tenant_po_box'    => ['page' => 1, 'x' => 100, 'y' => 78, 'size' => 12, 'width' => 30, 'align' => 'L'],

            // Building
            'property_lr_number' => ['page' => 1, 'x' => 45, 'y' => 95, 'size' => 12, 'width' => 35, 'align' => 'L'],
            'unit_code'          => ['page' => 1, 'x' => 95, 'y' => 95, 'size' => 12, 'width' => 40, 'align' => 'L'],
            'property_name'      => ['page' => 1, 'x' => 25, 'y' => 90, 'size' => 12, 'width' => 120, 'align' => 'L'],

            // Term: "from __ / __ / __ To __ / __ / __"
            'start_date_day'   => ['page' => 1, 'x' => 95,  'y' => 108, 'size' => 12, 'width' => 12, 'align' => 'C'],
            'start_date_month' => ['page' => 1, 'x' => 110, 'y' => 108, 'size' => 12, 'width' => 12, 'align' => 'C'],
            'start_date_year'  => ['page' => 1, 'x' => 125, 'y' => 108, 'size' => 12, 'width' => 18, 'align' => 'C'],
            'end_date_day'     => ['page' => 1, 'x' => 155, 'y' => 108, 'size' => 12, 'width' => 12, 'align' => 'C'],
            'end_date_month'   => ['page' => 1, 'x' => 170, 'y' => 108, 'size' => 12, 'width' => 12, 'align' => 'C'],
            'end_date_year'    => ['page' => 1, 'x' => 185, 'y' => 108, 'size' => 12, 'width' => 18, 'align' => 'C'],

            // Financials
            'monthly_rent'   => ['page' => 1, 'x' => 35, 'y' => 123, 'size' => 12, 'width' => 45, 'align' => 'R'],
            'deposit_amount' => ['page' => 1, 'x' => 35, 'y' => 130, 'size' => 12, 'width' => 45, 'align' => 'R'],
            'vat_amount'     => ['page' => 1, 'x' => 35, 'y' => 137, 'size' => 12, 'width' => 45, 'align' => 'R'],

            'reference_number' => ['page' => 1, 'x' => 25, 'y' => 35, 'size' => 12, 'width' => 60, 'align' => 'L'],
        ];
    }

    /**
     * Legacy signature keys kept for backward compatibility with existing templates that
     * already have pdf_coordinate_map rows in the DB using these keys.
     * The LeasePdfService prefers new named keys (lessor_signature, lessee_signature …)
     * over these old keys when both are present.
     *
     * @return array<string, array{page: int, x: float, y: float, width: float, height: float}>
     */
    public static function legacySignaturePlaceholders(): array
    {
        return [
            'tenant_signature'   => ['page' => 2, 'x' => 140, 'y' => 240, 'width' => 80, 'height' => 30],
            'manager_signature'  => ['page' => 2, 'x' => 140, 'y' => 280, 'width' => 80, 'height' => 30, 'anchor' => 'above'],
            'witness_signature'  => ['page' => 2, 'x' =>  20, 'y' => 260, 'width' => 50, 'height' => 20],
            'advocate_signature' => ['page' => 2, 'x' =>  20, 'y' => 280, 'width' => 45, 'height' => 18, 'anchor' => 'beside'],
        ];
    }

    /**
     * Detailed signing-page coordinate map (6 boxes, 2 columns).
     *
     * These are INITIAL ESTIMATES for the standard Chabrin template layout on A4.
     * Run  php artisan leases:preview-coordinates {templateId}  to generate a
     * labelled overlay PDF and fine-tune x/y values against the actual form boxes.
     *
     * @param  int  $page  Physical page number that holds the signing section.
     *                     Micro Dwelling = 2, Major Dwelling = 5, Commercial = 7.
     * @return array<string, array{page: int, x: float, y: float, size?: int, width?: float, height?: float, align?: string}>
     */
    public static function signingPage(int $page = 2): array
    {
        // ─── Layout guide (A4 = 210 × 297 mm) ───────────────────────────────
        // Left column  (Lessor side):  x  8 – 101 mm
        // Right column (Lessee side):  x 113 – 207 mm
        // Rows (y from top):
        //   Box 1 / 4  – party name/ID/sig: y 35 – 70
        //   Box 2 / 5  – witness:           y 75 – 110
        //   Box 3 / 6  – advocate:          y 118 – 170
        // ─────────────────────────────────────────────────────────────────────

        return [
            // ── Box 1: LESSOR (left side, top) ───────────────────────────────
            'lessor_sig_name'  => ['page' => $page, 'x' =>   8, 'y' => 38, 'size' => 9, 'width' => 67, 'align' => 'L'],
            'lessor_sig_id'    => ['page' => $page, 'x' =>  78, 'y' => 38, 'size' => 9, 'width' => 24, 'align' => 'L'],
            'lessor_sig_date'  => ['page' => $page, 'x' =>   8, 'y' => 62, 'size' => 8, 'width' => 55, 'align' => 'L'],
            'lessor_signature' => ['page' => $page, 'x' =>   8, 'y' => 44, 'width' => 65, 'height' => 16],

            // ── Box 2: LESSOR WITNESS (left side, middle) ────────────────────
            'lessor_witness_name'      => ['page' => $page, 'x' =>  8, 'y' => 78, 'size' => 9, 'width' => 67, 'align' => 'L'],
            'lessor_witness_id'        => ['page' => $page, 'x' => 78, 'y' => 78, 'size' => 9, 'width' => 24, 'align' => 'L'],
            'lessor_witness_date'      => ['page' => $page, 'x' =>  8, 'y' => 98, 'size' => 8, 'width' => 55, 'align' => 'L'],
            'lessor_witness_signature' => ['page' => $page, 'x' =>  8, 'y' => 83, 'width' => 65, 'height' => 16],

            // ── Box 3: LESSOR ADVOCATE (left side, bottom) ───────────────────
            'lessor_advocate_name'      => ['page' => $page, 'x' =>  8, 'y' => 120, 'size' => 9, 'width' => 67, 'align' => 'L'],
            'lessor_advocate_firm'      => ['page' => $page, 'x' =>  8, 'y' => 127, 'size' => 9, 'width' => 67, 'align' => 'L'],
            'lessor_advocate_lsk'       => ['page' => $page, 'x' =>  8, 'y' => 134, 'size' => 9, 'width' => 45, 'align' => 'L'],
            'lessor_advocate_date'      => ['page' => $page, 'x' => 57, 'y' => 134, 'size' => 8, 'width' => 42, 'align' => 'L'],
            'lessor_advocate_signature' => ['page' => $page, 'x' =>  8, 'y' => 142, 'width' => 52, 'height' => 18],
            'lessor_advocate_stamp'     => ['page' => $page, 'x' => 63, 'y' => 140, 'width' => 24, 'height' => 22],

            // ── Box 4: LESSEE (right side, top) ──────────────────────────────
            'lessee_sig_name'  => ['page' => $page, 'x' => 113, 'y' => 38, 'size' => 9, 'width' => 67, 'align' => 'L'],
            'lessee_sig_id'    => ['page' => $page, 'x' => 183, 'y' => 38, 'size' => 9, 'width' => 24, 'align' => 'L'],
            'lessee_sig_date'  => ['page' => $page, 'x' => 113, 'y' => 62, 'size' => 8, 'width' => 55, 'align' => 'L'],
            'lessee_signature' => ['page' => $page, 'x' => 113, 'y' => 44, 'width' => 65, 'height' => 16],

            // ── Box 5: LESSEE WITNESS (right side, middle) ───────────────────
            'lessee_witness_name'      => ['page' => $page, 'x' => 113, 'y' => 78, 'size' => 9, 'width' => 67, 'align' => 'L'],
            'lessee_witness_id'        => ['page' => $page, 'x' => 183, 'y' => 78, 'size' => 9, 'width' => 24, 'align' => 'L'],
            'lessee_witness_date'      => ['page' => $page, 'x' => 113, 'y' => 98, 'size' => 8, 'width' => 55, 'align' => 'L'],
            'lessee_witness_signature' => ['page' => $page, 'x' => 113, 'y' => 83, 'width' => 65, 'height' => 16],

            // ── Box 6: LESSEE ADVOCATE (right side, bottom) ──────────────────
            'lessee_advocate_name'      => ['page' => $page, 'x' => 113, 'y' => 120, 'size' => 9, 'width' => 67, 'align' => 'L'],
            'lessee_advocate_firm'      => ['page' => $page, 'x' => 113, 'y' => 127, 'size' => 9, 'width' => 67, 'align' => 'L'],
            'lessee_advocate_lsk'       => ['page' => $page, 'x' => 113, 'y' => 134, 'size' => 9, 'width' => 45, 'align' => 'L'],
            'lessee_advocate_date'      => ['page' => $page, 'x' => 162, 'y' => 134, 'size' => 8, 'width' => 42, 'align' => 'L'],
            'lessee_advocate_signature' => ['page' => $page, 'x' => 113, 'y' => 142, 'width' => 52, 'height' => 18],
            'lessee_advocate_stamp'     => ['page' => $page, 'x' => 168, 'y' => 140, 'width' => 24, 'height' => 22],
        ];
    }
}
