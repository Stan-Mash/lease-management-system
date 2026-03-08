<?php
// find_page3.php - Extract rectangles and lines from page 3 specifically
// Uses FPDI to get each page's raw content stream, then parses it
require '/var/www/chips/vendor/autoload.php';
$app = require_once '/var/www/chips/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$pdfPath = '/var/www/chips/storage/app/templates/leases/CHABRIN  AGENCIES TENANCY LEASE AGREEMENT - COMMERCIAL LEASE.pdf';

// We'll use smalot to extract text with approximate positions on page 3
// and also do raw stream extraction per-page if possible

// Method: use FPDI to import page 3 and get its dimensions
$pdf = new \setasign\Fpdi\Fpdi();
$count = $pdf->setSourceFile($pdfPath);
echo "Total PDF pages: $count\n\n";

// Extract raw content from the PDF binary, but try to track which stream
// belongs to which page by counting stream blocks
$raw = file_get_contents($pdfPath);

// Split into streams
preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $all_streams, PREG_SET_ORDER);
echo "Total streams found: " . count($all_streams) . "\n\n";

$scale = 25.4 / 72.0;
$pageH = 792; // US Letter pt

// Decode each stream and look for drawing commands
// Show which streams have significant content (many drawing ops)
echo "=== All streams with rectangles/lines, indexed ===\n";
foreach ($all_streams as $si => $sm) {
    $data = $sm[1];
    $decoded = false;
    foreach (['zlib_decode', 'gzuncompress', 'gzinflate'] as $fn) {
        $r = @$fn($data);
        if ($r !== false && strlen($r) > 10) { $decoded = $r; break; }
    }
    if (!$decoded) continue;

    // Count rectangles and lines
    preg_match_all('/([\-\d.]+)\s+([\-\d.]+)\s+([\-\d.]+)\s+([\-\d.]+)\s+re/', $decoded, $rects, PREG_SET_ORDER);
    preg_match_all('/([\-\d.]+)\s+([\-\d.]+)\s+m\s+([\-\d.]+)\s+([\-\d.]+)\s+l/', $decoded, $lines, PREG_SET_ORDER);

    $nRect = count($rects);
    $nLine = count($lines);
    if ($nRect === 0 && $nLine === 0) continue;

    echo "\n--- Stream #$si ($nRect rects, $nLine lines) ---\n";
    echo "  First 100 decoded chars: " . substr(preg_replace('/\s+/',' ',$decoded), 0, 100) . "\n";

    // Show all rects (small ones = form fields)
    foreach ($rects as $r) {
        $x = (float)$r[1]; $y = (float)$r[2]; $w = (float)$r[3]; $h = (float)$r[4];
        $xmm = round($x*$scale,1);
        $ymm = round(($pageH - $y - abs($h))*$scale, 1);
        $wmm = round(abs($w)*$scale,1);
        $hmm = round(abs($h)*$scale,1);
        if ($hmm < 2) { // thin = underline
            echo "  [rect-underline] x=$xmm, y=$ymm, w=$wmm\n";
        }
    }
    // Show all horizontal lines
    foreach ($lines as $l) {
        $x1=(float)$l[1]; $y1=(float)$l[2]; $x2=(float)$l[3]; $y2=(float)$l[4];
        if (abs($y1-$y2)<0.5 && abs($x2-$x1)>10) {
            $xmm = round(min($x1,$x2)*$scale,1);
            $ymm = round(($pageH - $y1)*$scale, 1);
            $wmm = round(abs($x2-$x1)*$scale,1);
            echo "  [hline] x=$xmm, y=$ymm, w=$wmm\n";
        }
    }
}

// Also: show text+coords from smalot for pages 2 and 3
echo "\n\n=== smalot text details pages 2-3 ===\n";
try {
    $parser = new \Smalot\PdfParser\Parser();
    $doc = $parser->parseFile($pdfPath);
    $pages = $doc->getPages();
    foreach ([1, 2] as $pi) { // 0-indexed (1=page2, 2=page3)
        if (!isset($pages[$pi])) continue;
        echo "\nPage " . ($pi+1) . " full text:\n";
        echo $pages[$pi]->getText() . "\n";
    }
} catch (Exception $e) {
    echo "smalot error: " . $e->getMessage() . "\n";
}
