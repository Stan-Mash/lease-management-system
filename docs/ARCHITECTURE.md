# Architecture Reference — Chabrin Lease Management

## Models (app/Models/)

### Core
- User, Tenant, Landlord, Property, Unit, Lease, Zone, LeaseDocument, LeaseTemplate

### Supporting
- LeaseApproval, LeaseAuditLog, LeaseEdit, LeaseHandover, LeasePrintLog
- LeaseLawyerTracking, LeaseCopyDistribution, DigitalSignature, OTPVerification
- LeaseEscalation, TenantEvent, Guarantor, Lawyer, RentEscalation
- Role, RoleAuditLog, DocumentAudit

## Filament Resources (app/Filament/Resources/)
Pattern: `ResourceName/{Pages,Schemas,Tables}/`

| Resource | Notes |
|---|---|
| Landlords/LandlordResource | — |
| Properties/PropertyResource | — |
| Units/UnitResource | — |
| Tenants/TenantResource | — |
| Leases/LeaseResource | `modifyQueryUsing()` with eager-loading, prevents N+1 |
| Users/UserResource | — |
| Roles/RoleResource | — |
| LeaseDocumentResource | — |
| LeaseTemplateResource | `blade_content` validated by TemplateSanitizer on save |
| LawyerResource | — |

## Services (app/Services/)

| Service | Category |
|---|---|
| LeaseReferenceService, SerialNumberService, QRCodeService, OTPService, DigitalSigningService | Core |
| DocumentUploadService, DocumentCompressionService, TemplateRenderService | Documents |
| LeaseDisputeService, LeaseRenewalService, LandlordApprovalService, RoleService, TenantEventService | Business |
| TemplateSanitizer | Security — validates Blade lease templates (blocks `system`, `exec`, `eval`, etc.) |
| DashboardStatsService | Caching — 5-min cache, invalidated by LeaseObserver on workflow_state change |

## Enums (app/Enums/)
LeaseWorkflowState, DocumentStatus, DocumentQuality, DocumentSource, TenantEventType,
PreferredLanguage, UnitStatus, DisputeReason, UserRole

## Exceptions (app/Exceptions/)
InvalidLeaseTransitionException, LeaseApprovalException, LeaseVerificationFailedException,
OTPRateLimitException, OTPSendingException, SerialNumberGenerationException, SMSSendingException,
LeaseSigningException — factory methods: `alreadySigned()`, `otpNotVerified()`, `invalidState()`

## Helpers (app/Helpers/)
**Money** — BCMath-based monetary arithmetic
- `Money::add()`, `subtract()`, `multiply()`, `divide()`, `escalate()`, `arrears()`, `format()`
- `Money::escalate('50000.00', '5.5')` → `'52750.00'`
- `MoneyHelper::applyRate()` for escalation calculations

## CHIPS Schema Column Mapping (SQL must use new names)
| Model | Old column | New column |
|---|---|---|
| Tenant | full_name | names |
| Tenant | id_number | national_id |
| Tenant | phone_number | mobile_number |
| Tenant | email | email_address |
| Tenant | kra_pin | pin_number |
| Property | name | property_name |
| Property | property_code | reference_number |
| Property | location | description |
| Unit | market_rent | rent_amount |

Old names have backward-compat accessors on models — safe in PHP, but SQL must use new names.

## DashboardStatsService
- `getAdminStats()` — company-wide counts
- `getZoneStats($zoneId)` — zone-scoped counts
- `invalidate($zoneId)` — manual cache invalidation
- Auto-invalidated by `LeaseObserver::updated()` on any `workflow_state` change
