<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Default PDF coordinate map for the standard "Particulars" layout.
 * Coordinates in mm; font size 12; width/align used so text stays inside printed boxes.
 * Use with templates:apply-default-coordinates to fill templates that have a PDF but no map.
 */
class DefaultLeasePdfCoordinateMap
{
    /** Default map for page 1 "Particulars" (date, lessor, lessee, building, term, rent, deposit, VAT). */
    public static function particularsPage1(): array
    {
        return [
            // Date: "dated the __ day on the month of __ in the year __"
            'lease_date_day'   => ['page' => 1, 'x' => 25, 'y' => 45, 'size' => 12, 'width' => 12, 'align' => 'L'],
            'lease_date_month' => ['page' => 1, 'x' => 42, 'y' => 45, 'size' => 12, 'width' => 28, 'align' => 'L'],
            'lease_date_year'  => ['page' => 1, 'x' => 75, 'y' => 45, 'size' => 12, 'width' => 18, 'align' => 'L'],

            // Lessor
            'landlord_name'    => ['page' => 1, 'x' => 25, 'y' => 58, 'size' => 12, 'width' => 75, 'align' => 'L'],
            'landlord_po_box'   => ['page' => 1, 'x' => 105, 'y' => 58, 'size' => 12, 'width' => 25, 'align' => 'L'],

            // Lessee
            'tenant_name'      => ['page' => 1, 'x' => 25, 'y' => 72, 'size' => 12, 'width' => 70, 'align' => 'L'],
            'tenant_id_number'  => ['page' => 1, 'x' => 100, 'y' => 72, 'size' => 12, 'width' => 35, 'align' => 'L'],
            'tenant_po_box'     => ['page' => 1, 'x' => 100, 'y' => 78, 'size' => 12, 'width' => 30, 'align' => 'L'],

            // Building
            'property_lr_number' => ['page' => 1, 'x' => 45, 'y' => 95, 'size' => 12, 'width' => 35, 'align' => 'L'],
            'unit_code'        => ['page' => 1, 'x' => 95, 'y' => 95, 'size' => 12, 'width' => 40, 'align' => 'L'],
            'property_name'    => ['page' => 1, 'x' => 25, 'y' => 90, 'size' => 12, 'width' => 120, 'align' => 'L'],

            // Term: "from __ / __ / __ To __ / __ / __"
            'start_date_day'   => ['page' => 1, 'x' => 95, 'y' => 108, 'size' => 12, 'width' => 12, 'align' => 'C'],
            'start_date_month' => ['page' => 1, 'x' => 110, 'y' => 108, 'size' => 12, 'width' => 12, 'align' => 'C'],
            'start_date_year'  => ['page' => 1, 'x' => 125, 'y' => 108, 'size' => 12, 'width' => 18, 'align' => 'C'],
            'end_date_day'     => ['page' => 1, 'x' => 155, 'y' => 108, 'size' => 12, 'width' => 12, 'align' => 'C'],
            'end_date_month'   => ['page' => 1, 'x' => 170, 'y' => 108, 'size' => 12, 'width' => 12, 'align' => 'C'],
            'end_date_year'    => ['page' => 1, 'x' => 185, 'y' => 108, 'size' => 12, 'width' => 18, 'align' => 'C'],

            // Financials
            'monthly_rent'    => ['page' => 1, 'x' => 35, 'y' => 123, 'size' => 12, 'width' => 45, 'align' => 'R'],
            'deposit_amount'  => ['page' => 1, 'x' => 35, 'y' => 130, 'size' => 12, 'width' => 45, 'align' => 'R'],
            'vat_amount'      => ['page' => 1, 'x' => 35, 'y' => 137, 'size' => 12, 'width' => 45, 'align' => 'R'],

            'reference_number'=> ['page' => 1, 'x' => 25, 'y' => 35, 'size' => 12, 'width' => 60, 'align' => 'L'],

            // Signatures (page 2 placeholder; adjust x,y via Pick positions if needed)
            'tenant_signature'  => ['page' => 2, 'x' => 140, 'y' => 240, 'width' => 80, 'height' => 30],
            'manager_signature' => ['page' => 2, 'x' => 140, 'y' => 280, 'width' => 80, 'height' => 30],
        ];
    }
}
