# Chabrin Lease Management System

## Project
Enterprise lease management for Chabrin Agencies — leases, tenants, landlords, units, document workflows, digital signing, zone-based RBAC.

**Production:** https://leases-docs.chabrinagencies.com | Server: `ssh deploy@161.35.74.238` app at `/var/www/chips`
**GitHub:** git@github.com:Stan-Mash/lease-management-system.git

## Key Docs (read these, not this file, for detail)
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) — Models, services, enums, resources
- [docs/SECURITY_NOTES.md](docs/SECURITY_NOTES.md) — CSP nonce, PII encryption, OTP, file uploads, backups
- [docs/FINANCIAL_POLICY.md](docs/FINANCIAL_POLICY.md) — BCMath, MoneyHelper, no float arithmetic
- [docs/TIMEZONE.md](docs/TIMEZONE.md) — Africa/Nairobi, Carbon usage in commands
- [docs/SYNC_AND_DEPLOY.md](docs/SYNC_AND_DEPLOY.md) — Sync and deploy workflow
- [docs/SETUP_GUIDE.md](docs/SETUP_GUIDE.md) — Local setup

## Tech Stack
Laravel 11 + PHP 8.2+ | Filament 4.5 | PostgreSQL 16 | Tailwind CSS 4 + Vite 7
spatie/laravel-permission v6 | barryvdh/laravel-dompdf | maatwebsite/excel | Africa's Talking SMS

## CRITICAL Rules (always apply)
- **PII:** Never remove `'encrypted'` casts on Tenant/Landlord (`national_id`, `passport_number`, `pin_number`)
- **Finance:** Always use `App\Helpers\Money` or `MoneyHelper` — never native PHP float arithmetic
- **CSP:** All inline `<script>` tags must have `nonce="{{ $cspNonce }}"`
- **DB columns:** Use new CHIPS names in SQL — `names`, `national_id`, `mobile_number`, `email_address`, `pin_number`, `property_name`, `reference_number`, `rent_amount`
- **Timezone:** Use `now(config('app.timezone'))` in scheduled commands, never bare `Carbon::today()`
- **OTP:** 15-minute window, hashed storage, server-side expiry enforced in `OTPService::verify()`
- **Backups:** Use `.pgpass` temp file (chmod 0600), never `PGPASSWORD` env var

## Machine Detection
| Machine | Path Pattern | PHP | Composer |
|---|---|---|---|
| Work desktop | `C:\Users\IT SUPPORT\...` | `C:\Xampp\php\php.exe` | `C:\Xampp\php\php.exe "C:\Users\IT SUPPORT\AppData\Roaming\Composer\latest.phar"` |
| Home laptop | `C:\Users\kiman\...` | check PATH | check PATH |
| Server | `/var/www/chips` | PHP 8.4 system | system |

- Both local machines: `composer install --ignore-platform-reqs`, DB `chabrin_leases` / `postgres` / pw `123`
- Work desktop npm: `PATH="/c/nodejs/node-v22.14.0-win-x64:$PATH"` first

## Common Commands
```bash
php artisan serve               # dev server :8000
php artisan optimize:clear      # clear all caches
php artisan migrate             # run migrations
composer run pint               # format code
php artisan pii:encrypt --force # encrypt PII rows after adding cast
php artisan db:backup --compress
```

## Deploy
```bash
cd /var/www/chips && git pull origin main && php artisan migrate --force && php artisan optimize:clear
```

## Git / Commit Strategy
- Commit after each logical unit — model, migration, resource, bug fix (sessions can time out)
- Push every 2–3 commits or when switching context
- Feature branches → merge to main; GitHub is source of truth
