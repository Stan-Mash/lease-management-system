<?php
require '/var/www/chips/vendor/autoload.php';
$app = require_once '/var/www/chips/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== LEASE TEMPLATES ===\n\n";
foreach (\App\Models\LeaseTemplate::orderBy('id')->get() as $t) {
    $mapCount = is_array($t->pdf_coordinate_map) ? count($t->pdf_coordinate_map) : 0;
    $bladeLen = strlen((string)$t->blade_content);

    echo "ID={$t->id} | {$t->name}\n";
    echo "  source_pdf_path : " . ($t->source_pdf_path ?? 'NULL') . "\n";
    echo "  pdf_coord_map   : {$mapCount} fields\n";
    echo "  blade_content   : " . ($bladeLen > 0 ? "SET ({$bladeLen} bytes)" : "EMPTY") . "\n";

    if ($t->source_pdf_path) {
        $checks = [
            'storage_path(app/...) ' => storage_path('app/' . $t->source_pdf_path),
            'base_path(...)        ' => base_path($t->source_pdf_path),
        ];
        foreach ($checks as $label => $path) {
            echo "  file [$label]: " . (file_exists($path) ? 'EXISTS' : 'MISSING') . "\n    => $path\n";
        }
    }
    echo "\n";
}

echo "=== LEASES — template assignments (first 10) ===\n";
foreach (\App\Models\Lease::with('leaseTemplate')->orderBy('id')->limit(10)->get() as $l) {
    $tname = $l->leaseTemplate->name ?? 'NO TEMPLATE';
    echo "Lease #{$l->id} (ref={$l->reference_number}) => template_id={$l->lease_template_id} ({$tname})\n";
}

echo "\n=== HOW generate() DECIDES STRATEGY ===\n";
echo "Strategy 0 (PDF overlay) requires:\n";
echo "  1. template->source_pdf_path is set\n";
echo "  2. template->pdf_coordinate_map has entries\n";
echo "  3. The source PDF file EXISTS on disk\n";
echo "Strategy 1/2 (Blade DomPDF): fallback when above conditions not met\n";
