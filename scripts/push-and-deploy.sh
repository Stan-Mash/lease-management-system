#!/usr/bin/env bash
# Push to GitHub then deploy to DigitalOcean production (leases-docs.chabrinagencies.com).
# Run from project root: ./scripts/push-and-deploy.sh (or bash scripts/push-and-deploy.sh)
set -e
REMOTE="deploy@161.35.74.238"
APP_DIR="/var/www/chips"

echo "Pushing to GitHub..."
git push origin main

echo "Deploying to production (leases-docs.chabrinagencies.com)..."
ssh "$REMOTE" "cd $APP_DIR && git pull origin main && php artisan migrate --force && php artisan optimize:clear"

echo "Push and deploy complete."
