<?php
// update_coords_11_v2.php - Fix date fields and start_date_year for template 11
require '/var/www/chips/vendor/autoload.php';
$app = require_once '/var/www/chips/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Corrections based on rectangle extraction:
//
// BODY DATE (Particulars section, y=58.6 underlines):
//   - month box:  x=76.2,  y=58.6, w=31.1  → text at y=53.6 (58.6-5)
//   - year box:   x=130.5, y=58.6, w=25.8  → text at y=53.6
//   - day: no extracted box, estimated at x=42.0 (before "day of" text)
//
// TERM SECTION (y=149.6 underlines):
//   - start_day box:   x=105.2, y=149.6, w=5.7  → text at y=144.6
//   - start_month box: x=110.9, y=149.6, w=17.9 → text at y=144.6
//   - start_year box:  x=128.8, y=149.6, w=3.2  → text at y=144.6 (was at 118.0 — inside month box!)
//
// END DATE (y=156.8 underline, wide box x=84.7 w=41):
//   - day at x=84.7, month at x=93.5, year at x=103.0 → all at y=151.8 (unchanged)

$map = [
    // ── DATE in body Particulars section (FIXED: was targeting page header) ──
    'lease_date_day'     => ['page'=>2,'x'=>42.0, 'y'=>53.6,'size'=>12,'color'=>'FF0000'],
    'lease_date_month'   => ['page'=>2,'x'=>76.2, 'y'=>53.6,'size'=>12,'color'=>'FF0000'],
    'lease_date_year'    => ['page'=>2,'x'=>130.5,'y'=>53.6,'size'=>12,'color'=>'FF0000'],

    // ── Landlord section ──
    'landlord_name'      => ['page'=>2,'x'=>76.2, 'y'=>64.5,'size'=>12,'color'=>'FF0000'],
    'landlord_po_box'    => ['page'=>2,'x'=>100.3,'y'=>70.0,'size'=>12,'color'=>'FF0000'],

    // ── Tenant section ──
    'tenant_id_number'   => ['page'=>2,'x'=>117.7,'y'=>87.1,'size'=>12,'color'=>'FF0000'],
    'tenant_name'        => ['page'=>2,'x'=>76.2, 'y'=>91.9,'size'=>12,'color'=>'FF0000'],
    'tenant_po_box'      => ['page'=>2,'x'=>130.5,'y'=>97.3,'size'=>12,'color'=>'FF0000'],

    // ── Building / Property section ──
    'property_name'      => ['page'=>2,'x'=>108.0,'y'=>118.0,'size'=>12,'color'=>'FF0000'],
    'property_lr_number' => ['page'=>2,'x'=>144.3,'y'=>128.2,'size'=>12,'color'=>'FF0000'],
    'unit_code'          => ['page'=>2,'x'=>100.4,'y'=>133.7,'size'=>12,'color'=>'FF0000'],

    // ── Term section — START DATE (FIXED: year was at 118.0, inside month box) ──
    'start_date_day'     => ['page'=>2,'x'=>105.2,'y'=>144.6,'size'=>12,'color'=>'FF0000'],
    'start_date_month'   => ['page'=>2,'x'=>110.9,'y'=>144.6,'size'=>12,'color'=>'FF0000'],
    'start_date_year'    => ['page'=>2,'x'=>128.8,'y'=>144.6,'size'=>10,'color'=>'FF0000'],

    // ── Term section — END DATE (unchanged) ──
    'end_date_day'       => ['page'=>2,'x'=>84.7, 'y'=>151.8,'size'=>12,'color'=>'FF0000'],
    'end_date_month'     => ['page'=>2,'x'=>93.5, 'y'=>151.8,'size'=>12,'color'=>'FF0000'],
    'end_date_year'      => ['page'=>2,'x'=>103.0,'y'=>151.8,'size'=>12,'color'=>'FF0000'],

    // ── Financial section ──
    'monthly_rent'       => ['page'=>2,'x'=>87.2, 'y'=>164.4,'size'=>12,'color'=>'FF0000'],
    'deposit_amount'     => ['page'=>2,'x'=>86.9, 'y'=>175.3,'size'=>12,'color'=>'FF0000'],
    'vat_amount'         => ['page'=>2,'x'=>160.4,'y'=>219.1,'size'=>12,'color'=>'FF0000'],

    // ── Rent review ──
    'rent_review_years'  => ['page'=>2,'x'=>38.0, 'y'=>249.0,'size'=>12,'color'=>'FF0000'],
    'rent_review_rate'   => ['page'=>2,'x'=>108.0,'y'=>249.0,'size'=>12,'color'=>'FF0000'],

    // ── Signatures (page 7, unchanged) ──
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

// Verify what was saved
$saved = DB::selectOne("SELECT pdf_coordinate_map FROM lease_templates WHERE id = 11");
$savedMap = json_decode($saved->pdf_coordinate_map, true);

echo "\nKey coordinates after update:\n";
foreach (['lease_date_day','lease_date_month','lease_date_year','start_date_day','start_date_month','start_date_year','end_date_year'] as $k) {
    $v = $savedMap[$k] ?? 'MISSING';
    if (is_array($v)) {
        echo "  $k: x={$v['x']}, y={$v['y']}, size=" . ($v['size'] ?? '?') . "\n";
    } else {
        echo "  $k: $v\n";
    }
}
echo "\nDone.\n";
