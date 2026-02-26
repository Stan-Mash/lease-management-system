---
name: deploy
description: Deploy the Chabrin lease management app to the DigitalOcean production server
argument-hint: [optional: --skip-migrate]
user-invocable: true
allowed-tools: Bash(ssh *), Bash(git *)
---

# Deploy to Production

Deploy the app at `ssh deploy@161.35.74.238` → `/var/www/chips`

## Steps

1. **Check for pending local commits** — warn if current branch has unpushed commits
2. **SSH to server and pull latest main:**
   ```bash
   ssh deploy@161.35.74.238 "cd /var/www/chips && git pull origin main"
   ```
   If pull fails due to local changes: `git stash` first, then pull
3. **Run migrations** (unless `--skip-migrate` passed):
   ```bash
   ssh deploy@161.35.74.238 "cd /var/www/chips && php artisan migrate --force"
   ```
4. **Clear caches:**
   ```bash
   ssh deploy@161.35.74.238 "cd /var/www/chips && php artisan optimize:clear"
   ```
5. **Confirm** production URL is up: https://leases-docs.chabrinagencies.com

## Notes
- Always include `php artisan migrate --force` — migrations may be pending
- Server uses SSH URL: git@github.com:Stan-Mash/lease-management-system.git
- Auth: password auth (`ssh deploy@161.35.74.238`)
