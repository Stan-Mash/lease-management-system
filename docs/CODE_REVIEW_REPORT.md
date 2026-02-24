# Comprehensive Code Review Report — Chabrin Lease Management System

**Review Date:** February 2026  
**Scope:** `app/Http/Controllers`, `app/Models`, `routes`, `resources/views`, `database/migrations`  
**Focus:** OWASP Top 10, Performance, Architecture (SOLID/DRY), Lease Business Logic

---

## 1. Security Vulnerabilities (OWASP Top 10)

### 1.1 Authentication & Authorization / IDOR

#### CRITICAL: API Lease Verify Endpoint Exposes Lease Data Without Authentication

**Location:** `routes/api.php` (line 13–16), `app/Http/Controllers/Api/LeaseApiController.php` (lines 40–55)

**Issue:** `GET /api/v1/leases/{lease}/verify` is **not** behind `auth:sanctum`. Any unauthenticated user can enumerate lease IDs and retrieve reference number, serial number, tenant name, property name, dates, monthly rent, and workflow state. This is **Insecure Direct Object Reference (IDOR)** and **sensitive data exposure**.

**Why it matters:** Lease IDs are often sequential; an attacker can scrape PII and financial data for all leases.

**Fix:** Require verification to use the same proof as the public web verification (serial + hash), or move the endpoint behind auth and authorize with the Lease policy. The public verification page already uses `?serial=...&hash=...` and rate limiting. The API verify should either:
- Be removed from the public API and only used internally after hash verification, or
- Accept `serial` and `hash` in the request and return data only when the hash is valid (like the web controller).

**Refactored approach (recommended):** Make API verify require serial + hash and return the same payload only when valid; keep it rate-limited. See implemented fix below.

---

#### CRITICAL: Tenant, Landlord, and Property API Controllers Have No Authorization

**Location:** `app/Http/Controllers/Api/TenantApiController.php`, `LandlordApiController.php`, `PropertyApiController.php`

**Issue:** These controllers are behind `auth:sanctum` but:
- Do **not** use `AuthorizesRequests` or any policy.
- Do **not** scope results by the current user’s role/zone.
- Any authenticated user (e.g. a field officer in zone A) can call `GET /api/v1/tenants`, `GET /api/v1/landlords`, `GET /api/v1/properties` and receive **all** tenants, landlords, and properties in the system.

**Why it matters:** Violates least privilege and data isolation (e.g. zone-based RBAC). Exposes PII and business data across zones.

**Fix:** Scope listings and `show` by the same rules as leases:
- Reuse a shared “accessible” pattern (e.g. scope or policy) so that:
  - Super admins/admins see everything.
  - Zone managers and field officers see only entities in their zone (or linked to leases they can access).
- Apply `authorize('view', $tenant)` (or equivalent) on `show` and use a scoped query for `index` (e.g. tenants that appear in accessible leases, or landlords/properties in the user’s zone).

**Refactored code:** See implemented changes: TenantApiController, LandlordApiController, PropertyApiController scoped via lease/zone and policies or scopes.

---

#### HIGH: LandlordApprovalController — Undefined Variable in Catch Block

**Location:** `app/Http/Controllers/LandlordApprovalController.php` (e.g. `apiApprove`, `apiReject`)

**Issue:** If `Lease::where(...)->firstOrFail()` throws (e.g. 404), the variable `$lease` is never set, but the `catch` block logs `$leaseId` and `$lease` (undefined), which can cause a PHP notice/error and may leak context.

**Fix:** Initialize `$lease = null` before the try, or only log `$leaseId` in the catch. See implemented fix below.

---

#### MEDIUM: TemplatePreviewController — Potential LFI via View Name

**Location:** `app/Http/Controllers/TemplatePreviewController.php` — `previewDirect()` (lines 120–156)

**Issue:** `$viewName = $request->input('view', 'templates.lease-residential-major');` is passed directly to `Pdf::loadView($viewName, $data)`. If an attacker can control `view`, they could pass a path like `../../.env` or other blade paths, leading to **local file inclusion** or unexpected template rendering.

**Why it matters:** Even on internal routes, input used as view names should be strictly whitelisted.

**Fix:** Allow only a fixed set of view names (e.g. from a config or an array). See implemented fix below.

---

#### MEDIUM: LeaseDocumentPolicy N+1 and Null Lease

**Location:** `app/Policies/LeaseDocumentPolicy.php` — `hasAccess()`

**Issue:** `$lease = $document->lease;` triggers a query per document. When checking many documents (e.g. in a loop or Filament table), this causes N+1. Also, if a document has no linked lease (`lease_id` null), `$lease` is null and the policy returns `false` (correct) but the comment does not document this.

**Fix:** Use `$document->zone_id` for zone check when available (LeaseDocument has `zone_id`), so you avoid loading the lease for every check and handle documents without a lease consistently. If zone_id is not always set, keep the lease fallback but consider eager loading when authorizing in bulk.

---

### 1.2 Input Validation

- **Form Requests:** Tenant signing flows correctly use `SubmitSignatureRequest`, `VerifyOTPRequest`, `RejectLeaseRequest`. Good.
- **Landlord approve/reject:** Use inline `$request->validate(...)`. Prefer dedicated Form Request classes for consistency and reuse.
- **API transition:** `LeaseApiController::transition()` uses `request()->validate([...])`. Prefer a Form Request and validate that `new_state` is a valid `LeaseWorkflowState` enum value.
- **Mass assignment:** No use of `$request->all()` without protection was found. Models use `$fillable` appropriately.

---

### 1.3 Injection & XSS

- **SQL:** Raw SQL uses parameterized bindings (e.g. `FieldOfficerController` with `?` placeholders). `RevenueChartWidget::getDateSelectExpression()` builds expressions from a fixed `match($groupBy)` (hour/day/week/month), not user input — safe.
- **Blade:** Public-facing views (e.g. `lease/verify.blade.php`) use `{{ }}` for output. PDF views use `{!! $qr['svg'] !!}` for QR code SVG generated server-side (QRCodeService) — acceptable if QR content is never user-controlled. Ensure any future user-derived content in PDFs is escaped or sanitized.

---

### 1.4 CSRF & Rate Limiting

- **CSRF:** Filament uses `VerifyCsrfToken`; tenant signing portal sends `X-CSRF-TOKEN` in AJAX headers; landlord approval views use `@csrf`. Good.
- **Rate limiting:** Verification, tenant signing, OTP, and download routes have appropriate throttle limits. API uses `throttle:60,1` with stricter limits on verify. Good.

---

## 2. Performance & Database Optimization

### 2.1 N+1 Queries

- **LandlordApprovalController::index():** Runs three separate queries for pending, approved, and rejected leases with `with(['tenant', 'approvals'])`. Acceptable; consider a single query with conditional ordering/limits if the page becomes heavy.
- **FieldOfficerController:** Uses `with([...])` for landlord, tenant, approvals — good.
- **LeaseDocumentPolicy::hasAccess():** As above, use `zone_id` on the document where possible to avoid loading `lease` per document.

### 2.2 Database Indexing

- **Existing:** Migrations already add indexes on `workflow_state`, `end_date`, `property_id`, `assigned_field_officer_id`, `zone_id`, etc. (see `2026_02_11_000001_add_performance_indexes.php`).
- **Recommendation:** Ensure `leases.tenant_id` and `leases.landlord_id` have indexes (they do via `foreignId()` in the original migrations). If you have queries filtering by `start_date` or `created_at` for reporting, consider composite indexes for common filters (e.g. `(workflow_state, start_date)`).

### 2.3 Caching

- **PDF generation:** `DownloadLeaseController` already caches generated PDFs by lease/template/version — good.
- **Dashboard stats:** Widgets such as `LeaseStatsWidget` and approval counts run queries on each load. Consider short TTL cache (e.g. 1–5 minutes) for aggregate stats (e.g. total active leases, monthly revenue) with cache keys that invalidate when relevant data changes (e.g. tag-based).

---

## 3. Architecture & Code Quality

### 3.1 Fat Controllers

- **LandlordApprovalController:** Index and show build queries inline. Consider extracting “pending/approved/rejected for landlord” into a small service or scope (e.g. `LandlordApprovalService::getPendingLeasesForLandlord($landlordId)`) to keep the controller thin.
- **FieldOfficerController:** Logic is mostly query building and response formatting; acceptable. Could move dashboard stats into a dedicated service for reuse and testing.

### 3.2 Model Events vs Observers

- **LeaseObserver:** Serial number and QR generation live in the observer; no business logic cluttering the Lease model. Good.
- **RoleObserver:** Used; no issues noted.

### 3.3 DRY

- **LandlordApprovalController:** API responses for lease/tenant/approval mapping are duplicated between `apiIndex` and `apiShow`. Consider API Resources (e.g. `LandlordLeaseResource`) to centralize structure.
- **Landlord approval routes:** Web and API both call `LandlordApprovalService` — good.

### 3.4 Error Handling

- **LandlordApprovalController apiApprove/apiReject:** Catch block must not reference undefined `$lease` (see security fix above).
- **General:** Exceptions are caught and converted to JSON with generic messages while logging details — good. Ensure no stack traces or internal messages are exposed in production.

---

## 4. Business Logic (Lease Management)

### 4.1 Financial Calculations

- **Lease model:** `monthly_rent` and `deposit_amount` cast as `decimal:2`. Display uses `number_format(..., 2)`.
- **LeaseRenewalService::calculateRenewalRent():** Uses `round($lease->monthly_rent * (1 + $rate), 2)`. Float rounding can cause off-by-cent errors over many operations.
- **Recommendation:** For critical financial accuracy, consider storing amounts in **integer cents** (or smallest currency unit) and converting for display, or use `bcmath` for calculations. At minimum, document that rounding is to 2 decimal places and ensure all revenue/deposit calculations use the same convention.

### 4.2 Date Management

- **Lease:** `start_date`, `end_date` cast as `date`; Carbon is used in the codebase.
- **Recommendation:** Ensure `config/app.php` `timezone` and any user-facing date handling use the intended timezone (e.g. `Africa/Nairobi`). Use Carbon’s `timezone()` when comparing or displaying user-facing dates.

---

## 5. Breaking Change: API Verify Endpoint

**Old:** `GET /api/v1/leases/{lease}/verify` (any lease ID; no auth)  
**New:** `GET /api/v1/verify/lease?serial=...&hash=...` (requires serial and hash from QR/data; rate-limited)

Clients that relied on the old URL must switch to the new one and pass `serial` (lease serial or reference number) and `hash` (verification hash from the QR payload). This prevents IDOR.

---

## 6. Summary of Implemented Fixes (High Priority)

The following high-priority fixes are implemented in the codebase:

1. **LeaseApiController::verify()** — Require serial + hash for public API verify; return 404 when lease not found or hash invalid; no longer expose data by lease ID alone.
2. **TenantApiController, LandlordApiController, PropertyApiController** — Scope index/show by user (lease-accessible or zone); add authorization so only allowed users see each resource.
3. **LandlordApprovalController** — Fix undefined `$lease` in catch blocks (use only `$leaseId` for logging).
4. **TemplatePreviewController::previewDirect()** — Whitelist allowed view names for `view` and `type` input.
5. **LeaseDocumentPolicy** — Use `$document->zone_id` when available to avoid N+1 and document null-lease case.
6. **LandlordApprovalController apiIndex** — Use correct Tenant CHIPS columns (`names`, `mobile_number`, `email_address`) in API responses.
7. **Download filename sanitization** — `LeaseDocumentController` uses `App\Support\SafeDownloadFilename` for `Content-Disposition` so `original_filename` cannot inject headers (e.g. newlines, quotes) or path traversal.
8. **API health** — `GET /api/v1/health` returns `{ "status": "ok", "database": true|false }` for load balancers/monitoring; 503 when DB is down.

---

## 7. Recommended Next Steps (Implemented)

1. **Form Requests** — Added `LandlordApproveRequest`, `LandlordRejectRequest`, `LeaseTransitionRequest`; used in `LandlordApprovalController` and `LeaseApiController::transition()`.
2. **API Resources** — Added `LandlordPendingLeaseResource` and `LandlordLeaseDetailResource`; landlord approval API responses use them for consistent, DRY output.
3. **Dashboard caching** — Field officer dashboard cached per user/zone (5 min); cache **invalidated** when a lease is approved/rejected (version key in `LandlordApprovalService`). `LeaseStatsWidget` already had 5-min cache.
4. **Currency policy** — Added `docs/FINANCIAL_POLICY.md` and `App\Support\MoneyHelper` (round, format, **bcmath**: add, sub, mul, div, applyRate); `LeaseRenewalService::calculateRenewalRent()` uses `MoneyHelper` (bcmath when available).
5. **Timezone** — Default `Africa/Nairobi` in `config/app.php`; expanded `docs/TIMEZONE.md` with Carbon examples and links.
6. **CHIPS columns** — `PendingLeaseResource` and `FieldOfficerController` use tenant/landlord CHIPS columns (`names`, `mobile_number`, `email_address`) so field officer API responses match the schema.
