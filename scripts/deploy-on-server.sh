#!/usr/bin/env bash
# Run this script ON the DigitalOcean server (e.g. after: ssh deploy@161.35.74.238)
# Usage: cd /var/www/chips && ./scripts/deploy-on-server.sh
set -e
APP_DIR="${APP_DIR:-/var/www/chips}"
cd "$APP_DIR"
echo "Pulling from GitHub..."
git pull origin main
echo "Running migrations..."
php artisan migrate --force
echo "Clearing caches..."
php artisan optimize:clear
echo "Deploy complete."
