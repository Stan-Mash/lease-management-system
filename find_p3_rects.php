<?php
// find_p3_rects.php - Find rent review underline positions on page 3
// Uses smalot DataTm for text position + targeted stream analysis
require '/var/www/chips/vendor/autoload.php';
$app = require_once '/var/www/chips/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$pdfPath = '/var/www/chips/storage/app/templates/leases/CHABRIN  AGENCIES TENANCY LEASE AGREEMENT - COMMERCIAL LEASE.pdf';

$scale = 25.4 / 72.0;
$pageH = 792;

// ── 1. Get page 3 text with positions via smalot getDataTm ──
echo "=== Page 3 text with positions (DataTm) ===\n";
try {
    $parser = new \Smalot\PdfParser\Parser();
    $doc    = $parser->parseFile($pdfPath);
    $pages  = $doc->getPages();
    $page3  = $pages[2] ?? null; // 0-indexed, page3 = index 2

    if ($page3) {
        $dataTm = $page3->getDataTm();
        foreach ($dataTm as $item) {
            // Each item: [tm_matrix, text] or [position_array, text]
            if (!is_array($item) || count($item) < 2) continue;
            $tm   = $item[0]; // transformation matrix [a,b,c,d,e,f]
            $text = trim($item[1]);
            if ($text === '') continue;
            // e=x, f=y in PDF pts (origin bottom-left)
            if (is_array($tm) && count($tm) >= 6) {
                $xmm = round((float)$tm[4] * $scale, 1);
                $ymm = round(($pageH - (float)$tm[5]) * $scale, 1);
                echo "  x=$xmm, y=$ymm → \"$text\"\n";
            }
        }
    } else {
        echo "Page 3 not found\n";
    }
} catch (Exception $e) {
    echo "smalot error: " . $e->getMessage() . "\n";
}

// ── 2. Decode ALL streams, look for thin rects/lines in ALL streams ──
// Page 3 rectangles might be in streams we haven't fully decoded
echo "\n\n=== ALL thin rects (h<2mm) from all streams ===\n";
$raw = file_get_contents($pdfPath);
preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $allstreams, PREG_SET_ORDER);

$seen = [];
foreach ($allstreams as $si => $sm) {
    $data = $sm[1];
    $decoded = false;
    foreach (['zlib_decode', 'gzuncompress', 'gzinflate'] as $fn) {
        $r = @$fn($data);
        if ($r !== false && strlen($r) > 20) { $decoded = $r; break; }
    }
    if (!$decoded) continue;

    // All rectangles
    preg_match_all('/([\-\d.]+)\s+([\-\d.]+)\s+([\-\d.]+)\s+([\-\d.]+)\s+re/', $decoded, $rm, PREG_SET_ORDER);
    foreach ($rm as $r) {
        $x=(float)$r[1]; $y=(float)$r[2]; $w=(float)$r[3]; $h=(float)$r[4];
        $hmm = round(abs($h)*$scale,1);
        if ($hmm >= 2) continue; // skip thick rects
        $xmm = round($x*$scale,1);
        $ymm = round(($pageH - $y - abs($h))*$scale, 1);
        $wmm = round(abs($w)*$scale,1);
        $key = "$xmm,$ymm,$wmm";
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        echo "[stream#$si] x=$xmm, y=$ymm, w=$wmm\n";
    }

    // Horizontal lines using m...l operators
    preg_match_all('/([\-\d.]+)\s+([\-\d.]+)\s+m\s+([\-\d.]+)\s+([\-\d.]+)\s+l/', $decoded, $lm, PREG_SET_ORDER);
    foreach ($lm as $l) {
        $x1=(float)$l[1]; $y1=(float)$l[2]; $x2=(float)$l[3]; $y2=(float)$l[4];
        if (abs($y1-$y2) > 0.5 || abs($x2-$x1) < 10) continue;
        $xmm = round(min($x1,$x2)*$scale,1);
        $ymm = round(($pageH - $y1)*$scale, 1);
        $wmm = round(abs($x2-$x1)*$scale,1);
        $key = "line,$xmm,$ymm,$wmm";
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        echo "[stream#$si LINE] x=$xmm, y=$ymm, w=$wmm\n";
    }
}
echo "\nDone.\n";
