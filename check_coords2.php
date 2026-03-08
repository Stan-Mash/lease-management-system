<?php
// check_coords2.php - Extract all rectangles and FPDI dimensions for template 11
require '/var/www/chips/vendor/autoload.php';
$app = require_once '/var/www/chips/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LeaseTemplate;

$template = LeaseTemplate::find(11);
echo "Template 11: " . $template->name . "\n";

$sourceRelPath = $template->source_pdf_path;
// Try different path prefixes
$candidates = [
    '/var/www/chips/' . ltrim($sourceRelPath, '/'),
    '/var/www/chips/storage/app/' . ltrim($sourceRelPath, '/'),
    '/var/www/chips/storage/' . ltrim($sourceRelPath, '/'),
];
$pdfFullPath = null;
foreach ($candidates as $c) {
    echo "Trying: $c\n";
    if (file_exists($c)) { $pdfFullPath = $c; break; }
}
if (!$pdfFullPath) {
    // List what's in storage/app/templates
    echo "Not found. Listing storage/app/templates/leases:\n";
    foreach (glob('/var/www/chips/storage/app/templates/leases/*.pdf') as $f) {
        echo "  $f\n";
    }
    // Use the first one found
    $files = glob('/var/www/chips/storage/app/templates/leases/*.pdf');
    $pdfFullPath = $files[0] ?? null;
    echo "Using: $pdfFullPath\n";
}
echo "PDF: $pdfFullPath\n";
echo "Exists: " . (file_exists($pdfFullPath) ? 'YES' : 'NO') . "\n\n";

// FPDI page dimensions
echo "=== FPDI Page Dimensions ===\n";
for ($p = 1; $p <= 3; $p++) {
    try {
        $pdf = new \setasign\Fpdi\Fpdi();
        $pdf->setSourceFile($pdfFullPath);
        $tpl = $pdf->importPage($p);
        $size = $pdf->getTemplateSize($tpl);
        echo "Page $p: width={$size['width']}mm, height={$size['height']}mm\n";
    } catch (Exception $e) {
        echo "Page $p: ERROR - " . $e->getMessage() . "\n";
        break;
    }
}

// Extract rectangles from PDF binary
echo "\n=== All Rectangles (mm, sorted by y) ===\n";
$content = file_get_contents($pdfFullPath);
preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $content, $matches, PREG_SET_ORDER);

$rects = [];
foreach ($matches as $match) {
    $raw = $match[1];
    $decoded = false;
    foreach (['zlib_decode', 'gzuncompress', 'gzinflate'] as $fn) {
        $r = @$fn($raw);
        if ($r !== false && strlen($r) > 10) { $decoded = $r; break; }
    }
    if (!$decoded) continue;
    preg_match_all('/([\-\d.]+)\s+([\-\d.]+)\s+([\-\d.]+)\s+([\-\d.]+)\s+re/', $decoded, $rm, PREG_SET_ORDER);
    foreach ($rm as $rect) {
        $rects[] = [(float)$rect[1], (float)$rect[2], (float)$rect[3], (float)$rect[4]];
    }
}

// Convert PDF points (origin bottom-left) to mm (origin top-left)
// US Letter = 8.5in x 11in = 612pt x 792pt = 215.9mm x 279.4mm
$pageH_pt = 792;
$scale = 25.4 / 72.0;
$converted = [];
foreach ($rects as [$x,$y,$w,$h]) {
    $xmm = round($x * $scale, 1);
    $ymm = round(($pageH_pt - $y - abs($h)) * $scale, 1);
    $wmm = round(abs($w) * $scale, 1);
    $hmm = round(abs($h) * $scale, 1);
    $converted[] = [$xmm, $ymm, $wmm, $hmm];
}
usort($converted, fn($a,$b) => $a[1] <=> $b[1]);

$seen = [];
foreach ($converted as [$x,$y,$w,$h]) {
    $key = "$x,$y,$w,$h";
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    // Highlight the Term section (y=140-165)
    $flag = ($y >= 140 && $y <= 165) ? ' <<< TERM SECTION' : '';
    echo "x=$x, y=$y, w=$w, h=$h$flag\n";
}
echo "\nTotal unique rects: " . count($seen) . "\n";
