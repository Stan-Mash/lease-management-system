<?php
// find_sections.php - Find which PDF page has rent review section
// and extract line-segment based underlines (not just rectangles)
require '/var/www/chips/vendor/autoload.php';
$app = require_once '/var/www/chips/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$pdfPath = '/var/www/chips/storage/app/templates/leases/CHABRIN  AGENCIES TENANCY LEASE AGREEMENT - COMMERCIAL LEASE.pdf';
echo "PDF: $pdfPath\n\n";

// ── 1. Use smalot/pdfparser to find section keywords per page ──
try {
    $parser = new \Smalot\PdfParser\Parser();
    $doc    = $parser->parseFile($pdfPath);
    $pages  = $doc->getPages();
    echo "=== Page text snippets (first 400 chars each) ===\n";
    foreach ($pages as $i => $page) {
        $text = $page->getText();
        // Shorten for readability
        $snippet = substr(preg_replace('/\s+/', ' ', $text), 0, 400);
        echo "\n--- Page " . ($i + 1) . " ---\n$snippet\n";
    }
} catch (Exception $e) {
    echo "pdfparser error: " . $e->getMessage() . "\n";
}

// ── 2. Extract ALL rectangles AND horizontal line segments per stream ──
echo "\n\n=== Rectangles + Horizontal Lines (mm, sorted by y) ===\n";
$content = file_get_contents($pdfPath);
preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $content, $matches, PREG_SET_ORDER);

$elements = [];
foreach ($matches as $match) {
    $raw = $match[1];
    $decoded = false;
    foreach (['zlib_decode', 'gzuncompress', 'gzinflate'] as $fn) {
        $r = @$fn($raw);
        if ($r !== false && strlen($r) > 10) { $decoded = $r; break; }
    }
    if (!$decoded) continue;

    // Rectangles: x y w h re
    preg_match_all('/([\-\d.]+)\s+([\-\d.]+)\s+([\-\d.]+)\s+([\-\d.]+)\s+re/', $decoded, $rm, PREG_SET_ORDER);
    foreach ($rm as $r) {
        $elements[] = ['type'=>'rect', 'x'=>(float)$r[1], 'y'=>(float)$r[2], 'w'=>(float)$r[3], 'h'=>(float)$r[4]];
    }

    // Horizontal lines: x1 y1 m x2 y2 l  (where y1==y2)
    preg_match_all('/([\-\d.]+)\s+([\-\d.]+)\s+m\s+([\-\d.]+)\s+([\-\d.]+)\s+l/', $decoded, $lm, PREG_SET_ORDER);
    foreach ($lm as $l) {
        $x1 = (float)$l[1]; $y1 = (float)$l[2];
        $x2 = (float)$l[3]; $y2 = (float)$l[4];
        if (abs($y1 - $y2) < 0.5 && abs($x2 - $x1) > 5) { // horizontal, width > 5pt
            $elements[] = ['type'=>'line', 'x'=>min($x1,$x2), 'y'=>$y1, 'w'=>abs($x2-$x1), 'h'=>0];
        }
    }
}

// Convert PDF pts → mm (origin top-left, US Letter 792pt height)
$pageH = 792;
$scale = 25.4 / 72.0;
$converted = [];
foreach ($elements as $el) {
    $xmm = round($el['x'] * $scale, 1);
    $hmm = round(abs($el['h']) * $scale, 1);
    $ymm = round(($pageH - $el['y'] - ($el['type']==='rect' ? $hmm : 0)) * $scale, 1);
    $wmm = round(abs($el['w']) * $scale, 1);
    $converted[] = ['type'=>$el['type'], 'x'=>$xmm, 'y'=>$ymm, 'w'=>$wmm, 'h'=>$hmm];
}
usort($converted, fn($a,$b) => $a['y'] <=> $b['y']);

$seen = [];
foreach ($converted as $el) {
    $key = "{$el['type']},{$el['x']},{$el['y']},{$el['w']},{$el['h']}";
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $tag = $el['y'] >= 220 ? ' <<< LOWER PAGE' : '';
    echo "[{$el['type']}] x={$el['x']}, y={$el['y']}, w={$el['w']}, h={$el['h']}$tag\n";
}
echo "\nTotal unique elements: " . count($seen) . "\n";
