<?php
require '/var/www/chips/vendor/autoload.php';
$app = require '/var/www/chips/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Use reflection to get the BLOCKED_PATTERNS constant value at runtime
$ref = new ReflectionClass(App\Services\TemplateSanitizer::class);
$constants = $ref->getConstants();
$blocked = $constants['BLOCKED_PATTERNS'] ?? [];
echo "BLOCKED_PATTERNS count: " . count($blocked) . "\n";
echo "Contains 'mail': " . (in_array('mail', $blocked) ? "YES" : "NO") . "\n";
echo "Contains 'header': " . (in_array('header', $blocked) ? "YES" : "NO") . "\n";
echo "\nAll items:\n";
foreach ($blocked as $p) {
    echo "  - {$p}\n";
}

// Now check template 13 content against MAIL: pattern
$t = App\Models\LeaseTemplate::find(13);
$content = strtolower($t->blade_content);
echo "\nTemplate 13 blade_content length: " . strlen($t->blade_content) . "\n";

// Check what the actual sanitizer says
$sanitizer = app(App\Services\TemplateSanitizer::class);
try {
    $sanitizer->assertSafe($t->blade_content);
    echo "assertSafe: SAFE\n";
} catch (InvalidArgumentException $e) {
    echo "assertSafe BLOCKED: " . $e->getMessage() . "\n";
}
