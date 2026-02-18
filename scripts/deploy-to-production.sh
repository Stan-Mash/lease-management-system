#!/usr/bin/env bash
# Deploy Chabrin Lease System to DigitalOcean production (leases-docs.chabrinagencies.com).
# Run from your laptop or work desktop after pushing to GitHub.
set -e
REMOTE="deploy@chips-leases-app-01"
APP_DIR="/var/www/chips"

echo "Deploying to production (leases-docs.chabrinagencies.com)..."
ssh "$REMOTE" "cd $APP_DIR && git pull origin main && php artisan migrate --force && php artisan optimize:clear"
echo "Deploy complete."
