<?php
// update_coords_11_v3.php - Fix rent_review (page 3), add lease_years/months
// Changes vs v2:
//  - rent_review_years: page=2 y=249 → page=3 x=138.0 y=35.7 (DataTm shows "year(s)" at x=148.4, y=39.7; blank before it)
//  - rent_review_rate:  page=2 y=249 → page=3 x=82.0  y=41.0 (DataTm shows "%" at x=102.3, y=44.5; blank before it)
//  - lease_years:  NEW → page=2 x=76.2 y=144.7 size=10 (tiny box at y=149.7, w=4.3mm)
//  - lease_months: NEW → page=2 x=82.7 y=144.7 size=10 (tiny box at y=149.7, w=2.4mm)
//  - start_date_year: keep at x=128.8 but font stays size=9 (fits 2-digit year "26" in 3.2mm box)

require '/var/www/chips/vendor/autoload.php';
$app = require_once '/var/www/chips/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// ── Page 3 Rent Review coordinates (from smalot DataTm) ──────────────────────
// Line 1 (y=39.7mm on page 3): "...after each [YEARS] year(s) at a guide rate"
//   "year(s)" starts at x=148.4 → blank before it, from ~x=138 to 148.4
//   DataTm y=39.7mm → cell top = 39.7 - 4 = 35.7mm
//   rent_review_years: x=138.0, y=35.7, page=3
//
// Line 2 (y=44.5mm on page 3): "of [RATE]%. The review..."
//   "%" starts at x=102.3 → blank between "of " (~x=82) and "%"
//   DataTm y=44.5mm → cell top = 44.5 - 3.5 = 41.0mm
//   rent_review_rate: x=82.0, y=41.0, page=3

$map = [
    // ── DATE in body Particulars section ──────────────────────────────────────
    'lease_date_day'     => ['page'=>2,'x'=>42.0, 'y'=>53.6,'size'=>12,'color'=>'FF0000'],
    'lease_date_month'   => ['page'=>2,'x'=>76.2, 'y'=>53.6,'size'=>12,'color'=>'FF0000'],
    'lease_date_year'    => ['page'=>2,'x'=>130.5,'y'=>53.6,'size'=>12,'color'=>'FF0000'],

    // ── Landlord section ──────────────────────────────────────────────────────
    'landlord_name'      => ['page'=>2,'x'=>76.2, 'y'=>64.5,'size'=>12,'color'=>'FF0000'],
    'landlord_po_box'    => ['page'=>2,'x'=>100.3,'y'=>70.0,'size'=>12,'color'=>'FF0000'],

    // ── Tenant section ────────────────────────────────────────────────────────
    'tenant_id_number'   => ['page'=>2,'x'=>117.7,'y'=>87.1,'size'=>12,'color'=>'FF0000'],
    'tenant_name'        => ['page'=>2,'x'=>76.2, 'y'=>91.9,'size'=>12,'color'=>'FF0000'],
    'tenant_po_box'      => ['page'=>2,'x'=>130.5,'y'=>97.3,'size'=>12,'color'=>'FF0000'],

    // ── Building / Property section ───────────────────────────────────────────
    'property_name'      => ['page'=>2,'x'=>108.0,'y'=>118.0,'size'=>12,'color'=>'FF0000'],
    'property_lr_number' => ['page'=>2,'x'=>144.3,'y'=>128.2,'size'=>12,'color'=>'FF0000'],
    'unit_code'          => ['page'=>2,'x'=>100.4,'y'=>133.7,'size'=>12,'color'=>'FF0000'],

    // ── Term section — Lease DURATION (tiny boxes at y=149.7mm) ──────────────
    // Box widths: x=76.2 w=4.3mm (years), x=82.7 w=2.4mm (months)
    'lease_years'        => ['page'=>2,'x'=>76.2, 'y'=>144.7,'size'=>10,'color'=>'FF0000'],
    'lease_months'       => ['page'=>2,'x'=>82.7, 'y'=>144.7,'size'=>10,'color'=>'FF0000'],

    // ── Term section — START DATE (from __ / __ / __) ────────────────────────
    // Boxes at y=149.6: day(x=105.2,w=5.7), month(x=110.9,w=17.9), year(x=128.8,w=3.2)
    // start_date_year uses 2-digit format ('y') from LeasePdfService — fits 3.2mm box at size=9
    'start_date_day'     => ['page'=>2,'x'=>105.2,'y'=>144.6,'size'=>12,'color'=>'FF0000'],
    'start_date_month'   => ['page'=>2,'x'=>110.9,'y'=>144.6,'size'=>12,'color'=>'FF0000'],
    'start_date_year'    => ['page'=>2,'x'=>128.8,'y'=>144.6,'size'=>9, 'color'=>'FF0000'],

    // ── Term section — END DATE (To __/__/__) ────────────────────────────────
    // Single wide box at y=156.8, x=84.7, w=41mm
    'end_date_day'       => ['page'=>2,'x'=>84.7, 'y'=>151.8,'size'=>12,'color'=>'FF0000'],
    'end_date_month'     => ['page'=>2,'x'=>93.5, 'y'=>151.8,'size'=>12,'color'=>'FF0000'],
    'end_date_year'      => ['page'=>2,'x'=>103.0,'y'=>151.8,'size'=>12,'color'=>'FF0000'],

    // ── Financial section ────────────────────────────────────────────────────
    'monthly_rent'       => ['page'=>2,'x'=>87.2, 'y'=>164.4,'size'=>12,'color'=>'FF0000'],
    'deposit_amount'     => ['page'=>2,'x'=>86.9, 'y'=>175.3,'size'=>12,'color'=>'FF0000'],
    'vat_amount'         => ['page'=>2,'x'=>160.4,'y'=>219.1,'size'=>12,'color'=>'FF0000'],

    // ── RENT REVIEW — page 3 (corrected from page 2 y=249) ───────────────────
    // DataTm text positions on page 3:
    //   Line 1 y=39.7mm: "...after each [YEARS] year(s)..." — blank at x≈138-148.4
    //   Line 2 y=44.5mm: "of [RATE]%..."                   — blank at x≈82-102.3
    'rent_review_years'  => ['page'=>3,'x'=>138.0,'y'=>35.7,'size'=>12,'color'=>'FF0000'],
    'rent_review_rate'   => ['page'=>3,'x'=>82.0, 'y'=>41.0,'size'=>12,'color'=>'FF0000'],

    // ── Signatures (page 7) ───────────────────────────────────────────────────
    'manager_signature'  => ['page'=>7,'x'=>130.0,'y'=>75.0,'width'=>50,'height'=>12],
    'tenant_signature'   => ['page'=>7,'x'=>128.0,'y'=>135.0,'width'=>50,'height'=>12],
    'advocate_signature' => ['page'=>7,'x'=>139.0,'y'=>177.0,'width'=>50,'height'=>15],
];

$json = json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
$rows = DB::update(
    "UPDATE lease_templates SET pdf_coordinate_map = ? WHERE id = 11",
    [$json]
);
echo "Updated template 11: $rows row(s) affected\n";

// Verify key fields
$saved = DB::selectOne("SELECT pdf_coordinate_map FROM lease_templates WHERE id = 11");
$savedMap = json_decode($saved->pdf_coordinate_map, true);

echo "\nKey fields after update:\n";
$check = ['lease_years','lease_months','start_date_year','rent_review_years','rent_review_rate'];
foreach ($check as $k) {
    $v = $savedMap[$k] ?? 'MISSING';
    if (is_array($v)) {
        echo "  $k: page={$v['page']}, x={$v['x']}, y={$v['y']}, size=".($v['size']??'?')."\n";
    } else {
        echo "  $k: $v\n";
    }
}
echo "\nDone. Total fields: " . count($savedMap) . "\n";
