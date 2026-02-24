# Chabrin Lease Management System

## Project Overview
Enterprise lease management system for Chabrin Agencies. Manages property leases, tenants, landlords, units, document workflows, digital signing, and zone-based RBAC.

**Production URL:** https://leases-docs.chabrinagencies.com (hosted on DigitalOcean)
**Server:** DigitalOcean droplet — `ssh deploy@161.35.74.238`, app at `/var/www/chips`
**GitHub:** git@github.com:Stan-Mash/lease-management-system.git
**Sync (laptop, work desktop, GitHub, server):** See [docs/SYNC_AND_DEPLOY.md](docs/SYNC_AND_DEPLOY.md)

### Key documentation
- [docs/CODE_REVIEW_REPORT.md](docs/CODE_REVIEW_REPORT.md) — Security, performance, and architecture review; implemented fixes
- [docs/FINANCIAL_POLICY.md](docs/FINANCIAL_POLICY.md) — Currency, rounding, MoneyHelper, bcmath
- [docs/TIMEZONE.md](docs/TIMEZONE.md) — App timezone (Africa/Nairobi), Carbon usage
- [docs/SYNC_AND_DEPLOY.md](docs/SYNC_AND_DEPLOY.md) — Sync and deploy workflow
- [docs/DEPLOYMENT_GUIDE.md](docs/DEPLOYMENT_GUIDE.md) — Production deployment
- [docs/SETUP_GUIDE.md](docs/SETUP_GUIDE.md) — Local setup and testing

## Tech Stack
- **Framework:** Laravel 11 + PHP 8.2+
- **Admin UI:** Filament 4.5
- **Database:** PostgreSQL 16 (local port 5432, user: postgres, db: chabrin_leases)
- **RBAC:** spatie/laravel-permission v6
- **PDF:** barryvdh/laravel-dompdf
- **Excel:** maatwebsite/excel
- **SMS:** Africa's Talking API
- **Monitoring:** Laravel Pulse
- **Frontend:** Tailwind CSS 4 + Vite 7

## Architecture

### Models (app/Models/)
Core: User, Tenant, Landlord, Property, Unit, Lease, Zone, LeaseDocument, LeaseTemplate
Supporting: LeaseApproval, LeaseAuditLog, LeaseEdit, LeaseHandover, LeasePrintLog, LeaseLawyerTracking, LeaseCopyDistribution, DigitalSignature, OTPVerification, LeaseEscalation, TenantEvent, Guarantor, Lawyer, RentEscalation, Role, RoleAuditLog, DocumentAudit

### PII Encryption (CRITICAL — do not remove casts)
Tenant and Landlord models have `'encrypted'` casts on sensitive fields:
- `national_id`, `passport_number`, `pin_number` — stored as AES-256-CBC ciphertext
- These casts were applied 2026-02-23; 22,070 existing rows were encrypted via `php artisan pii:encrypt`
- **Never remove these casts** — doing so would expose raw ciphertext as gibberish in the UI
- If you add a new sensitive field, add the `'encrypted'` cast and run `pii:encrypt` again

### CHIPS Schema Column Mapping (CRITICAL)
The database uses CHIPS-aligned column names. Old names have backward-compat accessors on models but SQL queries MUST use new names:
- Tenant: full_name → names, id_number → national_id, phone_number → mobile_number, email → email_address, kra_pin → pin_number
- Property: name → property_name, property_code → reference_number, location → description
- Unit: market_rent → rent_amount

### Filament Resources (app/Filament/Resources/)
Each resource follows the pattern: ResourceName/{Pages,Schemas,Tables}/
- Landlords/LandlordResource
- Properties/PropertyResource
- Units/UnitResource
- Tenants/TenantResource
- Leases/LeaseResource — uses `modifyQueryUsing()` with eager-loading to prevent N+1
- Users/UserResource
- Roles/RoleResource
- LeaseDocumentResource
- LeaseTemplateResource — blade_content validated by TemplateSanitizer on save
- LawyerResource

### Services (app/Services/)
Core: LeaseReferenceService, SerialNumberService, QRCodeService, OTPService, DigitalSigningService
Documents: DocumentUploadService, DocumentCompressionService, TemplateRenderService
Business: LeaseDisputeService, LeaseRenewalService, LandlordApprovalService, RoleService, TenantEventService
New (2026-02-23): **TemplateSanitizer**, **DashboardStatsService**

### Enums (app/Enums/)
LeaseWorkflowState, DocumentStatus, DocumentQuality, DocumentSource, TenantEventType, PreferredLanguage, UnitStatus, DisputeReason, **UserRole** (added 2026-02-23)

### Exceptions (app/Exceptions/)
InvalidLeaseTransitionException, LeaseApprovalException, LeaseVerificationFailedException,
OTPRateLimitException, OTPSendingException, SerialNumberGenerationException, SMSSendingException,
**LeaseSigningException** (added 2026-02-23 — use factory methods: `alreadySigned()`, `otpNotVerified()`, `invalidState()`)

### Helpers (app/Helpers/)
**Money** (added 2026-02-23) — BCMath-based monetary arithmetic. Use for all rent/deposit/arrears calculations.
- `Money::add()`, `subtract()`, `multiply()`, `divide()`, `escalate()`, `arrears()`, `format()`
- **Never use native PHP float arithmetic for financial calculations** — use Money or MoneyHelper

## Security Architecture (implemented 2026-02-23)

### Content Security Policy
`SecurityHeaders` middleware generates a per-request nonce and shares it with Blade as `$cspNonce`.
All inline `<script>` tags in Blade views **must** include `nonce="{{ $cspNonce }}"`:
```html
<script nonce="{{ $cspNonce }}">
    // your inline JS here
</script>
```
Without the nonce, inline scripts will be blocked in production by CSP.

### Template Sanitizer
`TemplateSanitizer` service blocks dangerous PHP functions in Blade lease templates.
- Validation runs at form save (LeaseTemplateResource) AND at render time (TemplateRenderService)
- Blocked: `system`, `exec`, `eval`, `file_get_contents`, `curl_*`, `unserialize`, `include`, etc.
- If a legitimate template variable uses a word matching a blocked pattern, update the allowlist in `TemplateSanitizer::BLOCKED_PATTERNS` with care

### File Uploads (Tenant ID Documents)
Tenant ID uploads use `finfo()` magic-byte validation (not just MIME headers) and UUID filenames.
Files stored at `storage/app/private/tenant-id-documents/{lease_uuid}/{uuid}.{ext}` — never web-accessible.

### Database Backups
`db:backup` and `db:restore` commands use a temp `.pgpass` file (chmod 0600, deleted in `finally` block)
instead of the `PGPASSWORD` environment variable. Do not revert this to `PGPASSWORD`.

### OTP Security
- OTP validity window: **15 minutes** (was 30 — reduced to shrink replay window)
- Server-side expiry is enforced in `OTPService::verify()` regardless of client-side timer
- OTP codes are hashed with `Hash::make()` before storage — never stored plain-text

## Financial Policy
- **Always use BCMath** for monetary arithmetic — never native PHP floats
- Use `App\Helpers\Money` or `App\Support\MoneyHelper` — both are BCMath-backed
- `MoneyHelper::applyRate()` is the correct method for escalation calculations
- `Money::escalate('50000.00', '5.5')` → `'52750.00'` (exact, no float rounding)

## Timezone Policy
- App timezone: `Africa/Nairobi` (set in `config/app.php`)
- All date boundary calculations in commands **must** use `now(config('app.timezone'))` explicitly
- Never use bare `Carbon::today()` or `now()` in scheduled commands — server system tz may differ

## Dashboard Stats Caching
`DashboardStatsService` caches admin and zone-scoped lease counts for 5 minutes.
Cache is auto-invalidated by `LeaseObserver::updated()` on any `workflow_state` change.
- `DashboardStatsService::getAdminStats()` — company-wide counts
- `DashboardStatsService::getZoneStats($zoneId)` — zone-scoped counts
- `DashboardStatsService::invalidate($zoneId)` — manual invalidation

## Development Commands
```bash
php artisan serve                    # Start dev server (port 8000)
php artisan optimize:clear           # Clear all caches
php artisan migrate                  # Run migrations
composer run pint                    # Code formatting
composer run dev                     # Full dev environment
php artisan pii:encrypt --dry-run    # Preview PII encryption (run after adding encrypted casts)
php artisan pii:encrypt --force      # Encrypt existing plain-text PII rows in DB
php artisan db:backup --compress     # Create compressed PostgreSQL backup
php artisan db:restore               # Restore from backup (interactive)
```

## Deployment
- Server IP: `161.35.74.238`, user: `deploy`, app: `/var/www/chips`
- SSH: `ssh deploy@161.35.74.238` (password auth)
- Deploy command: `cd /var/www/chips && git pull origin main && php artisan migrate --force && php artisan optimize:clear`
- If server has local changes blocking pull: `git stash` first, then pull
- Remote uses SSH URL: git@github.com:Stan-Mash/lease-management-system.git
- **Always include `php artisan migrate --force`** in deploy — migrations may be pending

## Machine Identification

Claude must detect which machine it is on by checking the working directory path:

| Machine | Working Directory Pattern | PHP | DB Password | Node |
|---|---|---|---|---|
| **Work desktop** | `C:\Users\IT SUPPORT\...` | `C:\Xampp\php\php.exe` | `123` | `C:\nodejs\node-v22.14.0-win-x64\` |
| **Home laptop** | `C:\Users\kiman\...` | check PATH | `123` | check PATH |
| **Server** | `/var/www/chips` | PHP 8.4 (system) | n/a (no local DB) | system |

### Work Desktop specifics
- composer: `C:\Xampp\php\php.exe C:\Users\IT SUPPORT\AppData\Roaming\Composer\latest.phar`
- composer install always needs `--ignore-platform-reqs` (PHP 8.2 here, lock file targets PHP 8.4 from server)
- npm requires PATH export first: `PATH="/c/nodejs/node-v22.14.0-win-x64:$PATH"`
- Vite is pinned to v6 on this machine (laravel-vite-plugin 2.x incompatible with Vite 5)
- PHP extensions enabled in `C:\Xampp\php\php.ini`: pdo_pgsql, pgsql, gd, intl, zip

### Home laptop specifics
- Working directory: `C:\Users\kiman\Projects\chabrin-lease-system`
- DB password: `123` (same as work desktop)
- PHP/Node paths: check system PATH (likely different install locations)

### Both local machines
- DB: `chabrin_leases`, user: `postgres`, port: 5432
- `composer install --ignore-platform-reqs` needed if PHP < 8.4

## Git Workflow
- GitHub is the single source of truth
- All machines (laptop, desktop, server) push/pull through GitHub
- Feature branches for new work, merge to main

### Commit strategy — IMPORTANT
Claude must commit incrementally during multi-step tasks, not only at the end:
- Commit after each logical unit of work (model change, migration, resource update, bug fix)
- Do not batch up an entire feature before committing
- Reason: Claude Code sessions can time out — intermediate commits protect against losing work
- Each commit should be self-contained and not break the app (migrations + code change together)
- Push to GitHub after every 2-3 commits or whenever switching context

## Current State (2026-02-23)
- All machines and server in sync on `main` (commit `e694330`)
- Full security + performance audit completed and deployed (2026-02-23)
- **22,070 PII values encrypted** in production DB (national_id, passport_number, pin_number)
- New indexes applied to production DB via migration `2026_02_23_000001`
- Work desktop: PHP 8.2, PostgreSQL 15, Node 22, all migrations run
- Home laptop: may need `git pull origin main` before starting work
