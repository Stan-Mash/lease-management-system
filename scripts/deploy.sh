#!/usr/bin/env bash
# Commit (if changes), push to GitHub, then deploy to DigitalOcean production.
# Run from project root: ./scripts/deploy.sh
# Optional: ./scripts/deploy.sh "Your commit message"
set -e
REMOTE="deploy@161.35.74.238"
APP_DIR="/var/www/chips"
MSG="${1:-Deploy: sync latest changes}"

if [ -n "$(git status --porcelain)" ]; then
  echo "Uncommitted changes found. Committing with message: $MSG"
  git add -A
  git commit -m "$MSG"
else
  echo "No uncommitted changes."
fi

echo "Pushing to GitHub..."
git push origin main

echo "Deploying to production (leases-docs.chabrinagencies.com)..."
ssh "$REMOTE" "cd $APP_DIR && git pull origin main && php artisan migrate --force && php artisan optimize:clear"

echo "Deploy complete."
