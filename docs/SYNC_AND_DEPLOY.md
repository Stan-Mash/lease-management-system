# Sync & Deploy — Laptop, Work Desktop, GitHub, DigitalOcean

## Overview

| Node | Role | How it syncs |
|------|------|--------------|
| **GitHub** | Single source of truth | Everyone pushes/pulls here. |
| **Your laptop** | Dev machine 1 | `git push origin main` (or branch) → GitHub; `git pull origin main` ← GitHub. |
| **Work desktop** | Dev machine 2 | Same: push to GitHub, pull from GitHub. |
| **DigitalOcean server** | Production | Pulls from GitHub only (no direct push from server). You deploy by SSH + `git pull` + artisan. |

**Production:** https://leases-docs.chabrinagencies.com  
**Server:** DigitalOcean droplet — `deploy@chips-leases-app-01`, app at `/var/www/chips`  
**Repo:** git@github.com:Stan-Mash/lease-management-system.git

---

## Syncing all four together

### 1. GitHub is the hub

- **Laptop and work desktop:** Always commit and push to GitHub when you finish work. Pull from GitHub when you switch machine or start the day.
- **Server:** Never used for editing code. It only **pulls** from GitHub when you deploy.

### 2. Daily workflow (laptop or work desktop)

```bash
# Start of day (or after someone else may have pushed)
git pull origin main

# Do your work, then:
git add .
git commit -m "Your message"
git push origin main
```

On the **other** machine (e.g. work desktop if you pushed from laptop):

```bash
git pull origin main
```

That’s all you need to keep **laptop ↔ GitHub ↔ work desktop** in sync.

### 3. Deploying to DigitalOcean (production)

The server does **not** auto-pull. You deploy by running commands **on the server** (via SSH from your laptop or work desktop).

**Option A — SSH in and run commands manually**

From your laptop (or work desktop):

```bash
ssh deploy@chips-leases-app-01
cd /var/www/chips
git pull origin main
php artisan migrate --force
php artisan optimize:clear
exit
```

**Option B — One-line from your machine**

From your laptop or work desktop:

```bash
ssh deploy@chips-leases-app-01 "cd /var/www/chips && git pull origin main && php artisan migrate --force && php artisan optimize:clear"
```

**Option C — Deploy script (run from laptop/desktop)**

Save as `scripts/deploy-to-production.sh` (see below). Then:

```bash
chmod +x scripts/deploy-to-production.sh
./scripts/deploy-to-production.sh
```

Use the same flow from either laptop or work desktop: push to GitHub first, then run the SSH deploy so the server pulls the latest `main`.

---

## Summary diagram

```
┌─────────────────┐     push/pull      ┌─────────────┐     pull only (on deploy)     ┌──────────────────────┐
│  Your laptop    │ ◄────────────────► │   GitHub    │ ◄──────────────────────────── │  DigitalOcean server │
└─────────────────┘                    │  (source of │                               │  leases-docs.        │
         ▲                             │   truth)    │                               │  chabrinagencies.com │
         │ push/pull                   └─────────────┘                               └──────────────────────┘
         │                                     ▲
         │                                     │ push/pull
         │                                     │
┌─────────────────┐                           │
│  Work desktop   │ ◄──────────────────────────┘
└─────────────────┘
```

- **Laptop ↔ Desktop:** Sync only via GitHub (push from one, pull on the other).
- **Server:** Sync only by you running `git pull` (and migrations/optimize) over SSH when you want to deploy.

---

## Can Cursor/the assistant connect to DigitalOcean?

**No.** The assistant runs in your local Cursor environment and cannot:

- SSH into your DigitalOcean server
- Run commands on the server
- Access the server’s files or database directly

You (or your team) run SSH and deploy commands from your laptop or work desktop. The assistant can:

- Update project docs (e.g. CLAUDE.md, this file) with URL and server details
- Give you exact deploy commands and scripts (like Option C above)
- Help you fix code or config that you then push to GitHub and deploy yourself

---

## Optional: deploy script (run from laptop or desktop)

Create `scripts/deploy-to-production.sh`:

```bash
#!/usr/bin/env bash
set -e
REMOTE="deploy@chips-leases-app-01"
APP_DIR="/var/www/chips"

echo "Deploying to production (leases-docs.chabrinagencies.com)..."
ssh "$REMOTE" "cd $APP_DIR && git pull origin main && php artisan migrate --force && php artisan optimize:clear"
echo "Deploy complete."
```

Then run from the project root: `./scripts/deploy-to-production.sh` (after pushing to GitHub).

---

## Checklist: keeping everything in sync

- [ ] **Laptop:** Push to GitHub when you stop working; pull when you start (or after desktop pushed).
- [ ] **Work desktop:** Same — push when done, pull when you start (or after laptop pushed).
- [ ] **GitHub:** Default branch (e.g. `main`) is the source of truth; merge via PRs or direct push as per your workflow.
- [ ] **DigitalOcean:** Deploy only after pushing to GitHub; run `git pull` + `migrate` + `optimize:clear` on the server (via SSH or the script above).
