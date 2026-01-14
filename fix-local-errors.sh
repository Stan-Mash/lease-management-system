#!/bin/bash

echo "========================================"
echo "Fixing Chabrin Lease System Local Errors"
echo "========================================"
echo ""

echo "[1/5] Clearing all caches..."
php artisan clear-compiled
php artisan optimize:clear
echo ""

echo "[2/5] Regenerating autoloader..."
composer dump-autoload
echo ""

echo "[3/5] Publishing Filament assets..."
php artisan filament:upgrade
echo ""

echo "[4/5] Running migrations..."
php artisan migrate
echo ""

echo "[5/5] Final cache clear..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo ""

echo "========================================"
echo "All fixes applied successfully!"
echo "You can now run: php artisan serve"
echo "========================================"
