---
name: sync
description: Sync this machine with GitHub — pull latest main, check for conflicts, verify DB migrations are up to date
user-invocable: true
allowed-tools: Bash(git *), Bash(php artisan *)
---

# Sync with GitHub

## Steps

1. **Check current branch and status:**
   ```bash
   git status
   git branch --show-current
   ```

2. **If on a feature branch**, warn before switching. If on `main`, proceed.

3. **Stash uncommitted changes** if any:
   ```bash
   git stash
   ```

4. **Pull latest main:**
   ```bash
   git pull origin main
   ```

5. **Re-apply stash** if stashed:
   ```bash
   git stash pop
   ```

6. **Check for pending migrations:**
   ```bash
   php artisan migrate:status
   ```
   Run `php artisan migrate` if any are pending.

7. **Clear caches:**
   ```bash
   php artisan optimize:clear
   ```

8. **Report** what changed (commits pulled, migrations run, any conflicts).

## Notes
- GitHub is the single source of truth
- After sync, confirm app runs: `php artisan serve`
