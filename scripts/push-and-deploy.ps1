# Push to GitHub then deploy to DigitalOcean production (leases-docs.chabrinagencies.com).
# Run from project root on Windows (PowerShell).
$ErrorActionPreference = "Stop"
$REMOTE = "deploy@161.35.74.238"
$APP_DIR = "/var/www/chips"

Write-Host "Pushing to GitHub..."
git push origin main
if (-not $?) { exit 1 }

Write-Host "Deploying to production..."
ssh $REMOTE "cd $APP_DIR && git pull origin main && php artisan migrate --force && php artisan optimize:clear"
if (-not $?) { exit 1 }

Write-Host "Push and deploy complete."
