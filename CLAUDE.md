# Chabrin Lease Management System

## Project Overview
Enterprise lease management system for Chabrin Agencies. Manages property leases, tenants, landlords, units, document workflows, digital signing, and zone-based RBAC.

**Production URL:** https://leases-docs.chabrinagencies.com
**Server:** DigitalOcean droplet (deploy@chips-leases-app-01, /var/www/chips)
**GitHub:** git@github.com:Stan-Mash/lease-management-system.git

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

## Git Workflow
- GitHub is the single source of truth
- All machines (laptop, desktop, server) push/pull through GitHub
- Feature branches for new work, merge to main
- Commit after every meaningful change (protects against session timeouts)

## Current State (2026-02-17)
- Server and GitHub are in sync (server force-pushed as authority)
- Server has 19 commits of schema restructure work (CHIPS alignment, backward-compat accessors, role fixes, super admin setup)
- Local laptop synced to match
- Upload page (/admin/documents/upload) 500 error was fixed via column name corrections
