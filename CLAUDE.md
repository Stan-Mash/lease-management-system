# Chabrin Lease Management System

## Project Overview
Enterprise lease management system for Chabrin Agencies. Manages property leases, tenants, landlords, units, document workflows, digital signing, and zone-based RBAC.

**Production URL:** https://leases-docs.chabrinagencies.com (hosted on DigitalOcean)
**Server:** DigitalOcean droplet — `deploy@chips-leases-app-01`, app at `/var/www/chips`
**GitHub:** git@github.com:Stan-Mash/lease-management-system.git
**Sync (laptop, work desktop, GitHub, server):** See [docs/SYNC_AND_DEPLOY.md](docs/SYNC_AND_DEPLOY.md)

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
- Leases/LeaseResource
- Users/UserResource
- Roles/RoleResource
- LeaseDocumentResource
- LeaseTemplateResource
- LawyerResource

### Services (app/Services/)
Core: LeaseReferenceService, SerialNumberService, QRCodeService, OTPService, DigitalSigningService
Documents: DocumentUploadService, DocumentCompressionService, TemplateRenderService
Business: LeaseDisputeService, LeaseRenewalService, LandlordApprovalService, RoleService, TenantEventService

### Enums (app/Enums/)
LeaseWorkflowState, DocumentStatus, DocumentQuality, DocumentSource, TenantEventType, PreferredLanguage, UnitStatus, DisputeReason

## Development Commands
```bash
php artisan serve                    # Start dev server (port 8000)
php artisan optimize:clear           # Clear all caches
php artisan migrate                  # Run migrations
composer run pint                    # Code formatting
composer run dev                     # Full dev environment
```

## Deployment
- Server: deploy@chips-leases-app-01:/var/www/chips
- SSH key deployed for GitHub access
- Deploy: ssh into server, git pull origin main, php artisan optimize:clear
- Remote uses SSH URL: git@github.com:Stan-Mash/lease-management-system.git

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

## Current State (2026-02-18)
- Work desktop fully set up: PHP 8.2, PostgreSQL 15, Node 22, all migrations run
- Server and GitHub are in sync on `main`
- Home laptop previously synced; may need `git pull origin main` before starting work
