# Chabrin Lease Management System — Code Audit Report

**Date:** 2026-02-18  
**Scope:** Code Quality & Architecture, Security, Testing, Performance & Scalability  
**Auditor role:** Senior Principal Software Engineer & Security Architect

---

## Executive Summary

The codebase is well-structured with Filament resources, policies, zone-based RBAC, and consistent use of services. Several **critical security issues** (IDOR, missing authorization, hardcoded default password) and **missing security hardening** (no SecurityHeaders middleware) require immediate fixes. Testing is present (PHPUnit) but coverage is partial; a single-command test runner and coverage reporting are recommended. Performance indexes exist; a few N+1 risks and caching opportunities were noted.

---

## 1. Code Quality & Architecture

### 1.1 Findings

| Area | Finding | Severity |
|------|--------|----------|
| **SOLID** | Services are generally single-purpose (LeaseReferenceService, OTPService, etc.). Some controllers (e.g. `DownloadLeaseController`) mix PDF generation, caching, and response building — could be split into a dedicated PDF service for better SRP. | Low |
| **Error handling** | `DownloadLeaseController::generate()` catches exceptions and falls through to next strategy; in production with `debug=false` it can fall through without rethrowing, which is acceptable. Some API endpoints return generic 500 messages without logging context (e.g. `FieldOfficerController::dashboard`). | Medium |
| **Redundancy** | Tenant model has both CHIPS columns (`names`, `mobile_number`, `email_address`) and backward-compat accessors (`full_name`, `phone`, `email`) — documented and intentional. | Info |
| **Consistency** | Raw SQL in widgets/commands uses bound parameters (e.g. `LeaseStatsWidget`, `FieldOfficerController`) — good. `Lease::scopeAccessibleByUser` uses `whereRaw('1 = 0')` for “no access” — safe constant. | Good |

### 1.2 Recommendations

- **Centralize API error responses:** Use a custom exception handler or a trait to return consistent JSON structure (`success`, `message`, `errors`) and log with request/context.
- **Extract PDF generation:** Move cache key building, watermark injection, and template selection from `DownloadLeaseController` into a `LeasePdfService` to keep the controller thin and improve testability.

---

## 2. Security Hardening (Critical)

### 2.1 Critical Issues

| Issue | Location | Description |
|-------|----------|-------------|
| **IDOR – Lease PDF download** | `routes/web.php` + `DownloadLeaseController` | Routes `/leases/{lease}/download` and `/leases/{lease}/preview` are protected only by `auth`. Any authenticated user can download any lease PDF. **Fix:** Add policy check `$this->authorize('view', $lease)` in the controller (or `->can('view', 'lease')` on the route). |
| **IDOR – Landlord API** | `LandlordApprovalController::apiShow`, `apiApprove`, `apiReject` | These API methods do **not** call `verifyLandlordOwnership($landlordId)`. An authenticated Sanctum user could pass another landlord’s ID and lease ID and view/approve/reject that landlord’s leases. **Fix:** Call `$this->verifyLandlordOwnership($landlordId)` at the start of each of these three methods. |
| **Hardcoded default password** | `ChabrinExcelImportService.php` (staff import) | New users created during staff Excel import are given `Hash::make('password123')`. This is a weak default and should not be in code. **Fix:** Use a config-driven default (e.g. `config('import.staff_default_password')`) or generate a random temporary password and force reset on first login. |

### 2.2 High / Medium

| Issue | Location | Recommendation |
|-------|----------|----------------|
| **Security headers missing** | Application-wide | `SECURITY.md` documents a `SecurityHeaders` middleware (X-Frame-Options, X-Content-Type-Options, CSP, etc.) but the middleware does **not** exist in `app/Http/Middleware`, and is not registered. **Fix:** Create the middleware and register it in `bootstrap/app.php` (Laravel 11) so all responses get the headers. |
| **File upload validation** | `DocumentUploadService` | Filament forms use `acceptedFileTypes()` and `maxSize()`; the service trusts `$file->getMimeType()` and `getClientOriginalExtension()`. Add server-side validation against an allowlist of MIME types and optionally validate magic bytes for PDF/images to reduce risk of polyglot uploads. |
| **Lease verification info disclosure** | `LeaseVerificationController::show` | Public verification by `serial` + `hash` returns lease data only when hash is valid; when invalid, returning “Lease document not found” vs “Invalid verification code” can inform an attacker. Current behaviour is acceptable; consider rate limiting the verification endpoint (already throttled in API). | Info |

### 2.3 Positive Notes

- **Tenant signing:** `TenantSigningController::verifySignedUrlAndTenant()` validates signature and `tenant` query param — prevents IDOR on signing endpoints.
- **Landlord web flows:** `index`, `show`, `approve`, `reject` all call `verifyLandlordOwnership`.
- **Lease documents:** `LeaseDocumentController` uses `authorize('download'|'view', $leaseDocument)` and `LeaseDocumentPolicy` enforces zone-based access.
- **Sensitive config:** API keys and secrets are read via `config()` (e.g. Africa’s Talking); no hardcoded secrets in app code (except the staff import password above).
- **CSRF:** Filament panel uses `VerifyCsrfToken`; web routes are session-based and protected.

---

## 3. Testing Strategy & QA

### 3.1 Current State

- **Framework:** PHPUnit 11; Laravel’s `php artisan test` is used. Composer script: `"test": ["@php artisan config:clear --ansi", "@php artisan test"]`.
- **Suites:** Unit (`tests/Unit`), Feature (`tests/Feature`). No separate “integration” suite; feature tests cover HTTP and DB.
- **Coverage:** `phpunit.xml` has a `<source>` block for `app` but **no** `<coverage>` configuration (no reporter, no thresholds). So “test everything at once” is already `composer test` or `php artisan test`; coverage is not generated by default.

### 3.2 “Test Everything at Once”

- **Single command:**  
  `composer test`  
  or  
  `php artisan test`  
  This runs all tests in both suites. For CI, add:  
  `php artisan config:clear && php artisan test --parallel` (optional).

- **With coverage (optional):**  
  - Install `phpunit/phpunit` with coverage (Xdebug or PCOV).  
  - Run:  
    `php artisan test --coverage`  
  - To enforce a minimum coverage threshold, add to `phpunit.xml` (see Testing Guide below).

### 3.3 Critical Paths Lacking Coverage

| Area | Risk | Suggestion |
|------|------|------------|
| **DownloadLeaseController** | PDF generation, cache, authorization | Feature test: authenticated user with zone A cannot download lease in zone B (once policy is applied); or mock and assert `authorize('view', $lease)` is called. |
| **LandlordApprovalController API** | IDOR on apiShow/apiApprove/apiReject | Feature test: user without ownership of landlord gets 403 after adding `verifyLandlordOwnership`. |
| **DocumentUploadService** | File type/MIME abuse, path traversal | Unit/feature tests: reject disallowed MIME, reject path traversal in filename. |
| **LeaseDocumentController** | Policy and zone scoping | Feature test: user in zone A cannot download document for lease in zone B. |
| **TenantSigningController** | Invalid signature, wrong tenant in query | Feature tests: invalid signature returns 403; valid signature but wrong `tenant` param returns 403. |
| **Excel import (staff)** | Default password, validation | Feature test: staff import creates user and (e.g.) forces password reset or uses config default. |

### 3.4 Testing Guide (see below)

A concise “Testing Guide” section is provided at the end of this document with setup and exact commands.

---

## 4. Performance & Scalability

### 4.1 Findings

| Area | Finding | Recommendation |
|------|--------|----------------|
| **Indexes** | Migration `2026_02_11_000001_add_performance_indexes.php` adds indexes on leases (workflow_state, end_date, property_id, zone, etc.), users (role, zone_id), lease_documents (zone_id, status), etc. | Good; keep. Consider adding composite index for “active leases expiring soon” if that query is hot. |
| **N+1** | Most list/detail endpoints use `with()` (e.g. `LandlordApprovalController`, `FieldOfficerController`, `LeaseResource`). `LeaseDocumentPolicy::hasAccess` loads `$document->lease` per document — acceptable for single-document checks; if used in loops, consider eager loading in the caller. | Low risk; monitor. |
| **Caching** | `DownloadLeaseController` caches PDFs by lease/template/version; `LeaseStatsWidget` caches stats for 5 minutes. | Good. Consider cache tags for lease PDFs so they can be invalidated when lease is updated. |
| **Raw queries** | Widgets use `selectRaw` with bound parameters — no injection risk. `FieldOfficerController` uses `DB::table('lease_approvals')->selectRaw(...)` with bindings. | Safe. |
| **Memory** | No obvious unbounded loops loading large collections. Commands (e.g. expiry alerts) use chunking or scopes. | OK. |

### 4.2 Recommendations

- Add cache tags to lease PDF cache keys (e.g. `lease_pdf:{id}`) and clear tag when lease or template is updated.
- If “expiring soon” or dashboard queries grow, consider a materialized view or dedicated reporting indexes.

---

## 5. Refactored Code Blocks (Critical Fixes)

### 5.1 DownloadLeaseController — Add lease view authorization

Add at the start of `__invoke`, `preview`, and `generate` (or at the start of `generate` only, since both entry points call it):

```php
// In DownloadLeaseController::generate(), add immediately after the method signature:
public function __invoke(Lease $lease): SymfonyResponse
{
    $this->authorize('view', $lease);
    return $this->generate($lease, 'download');
}

public function preview(Lease $lease): SymfonyResponse
{
    $this->authorize('view', $lease);
    return $this->generate($lease, 'stream');
}
```

Ensure `LeasePolicy` is registered (Laravel 11 auto-discovers `LeasePolicy` for `Lease` model).

### 5.2 LandlordApprovalController — Verify landlord ownership in API methods

In `apiShow`, add as first line inside `try`:

```php
$this->verifyLandlordOwnership($landlordId);
```

In `apiApprove` and `apiReject`, add immediately after `$request->validate(...)` and before `try`:

```php
$this->verifyLandlordOwnership($landlordId);
```

(Or at the start of the `try` block in each.)

### 5.3 ChabrinExcelImportService — Remove hardcoded password **(implemented)**

**Done:** Replaced with `config('import.staff_default_password')`; if unset, `Str::random(32)` is used so imported staff must use "Forgot password". Added `config/import.php` with `staff_default_password` key (env: `IMPORT_STAFF_DEFAULT_PASSWORD`). Optional: set in `.env` for dev only; leave unset in production.

### 5.4 SecurityHeaders middleware **(implemented)**

**Done:** Created `app/Http/Middleware/SecurityHeaders.php` (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, Content-Security-Policy) and registered it in `bootstrap/app.php` via `$middleware->append()`.

---

## 6. Testing Guide — Run Full Test Suite

### 6.1 Prerequisites

- PHP 8.2+, Composer, PostgreSQL (or SQLite for tests).
- `.env.testing` or `APP_ENV=testing` with a dedicated DB (e.g. `DB_DATABASE=testing`).  
  `phpunit.xml` already sets `DB_DATABASE=testing`.

### 6.2 Run all tests (single command)

**Option A — Composer (recommended):**

```bash
composer test
```

**Option B — Makefile (if `make` is available):**

```bash
make test
```

**Option C — Artisan directly:**

```bash
php artisan config:clear && php artisan test
```

To run with parallelism (faster on multi-core):

```bash
php artisan test --parallel
# or: make test-parallel
```

### 6.3 Run with coverage (optional)

1. Enable a coverage driver (e.g. PCOV or Xdebug).
2. Run:

```bash
php artisan test --coverage
```

3. For a minimum coverage threshold, add to `phpunit.xml` inside `<source>`:

```xml
<coverage>
    <report>
        <html outputDirectory="build/coverage"/>
        <text outputFile="php://stdout" showUncoveredFiles="true"/>
    </report>
    <include>
        <directory suffix=".php">app</directory>
    </include>
    <exclude>
        <directory>app/Providers</directory>
    </exclude>
</coverage>
```

### 6.4 Linting / static analysis (already in project)

```bash
composer lint
```

This runs Pint and PHPStan (`composer.json` scripts: `pint:test`, `analyse`).

### 6.5 CI/CD suggestion

Single step to run tests and lint:

```yaml
- run: composer install --no-interaction
- run: cp .env.example .env.testing && php artisan key:generate --env=testing
- run: php artisan config:clear && php artisan test --parallel
- run: composer lint
```

Or with Makefile:

```yaml
- run: make test-parallel
- run: make lint
```

---

## 7. Prioritized To-Do List

### Immediate (Critical — do first)

1. **Lease PDF IDOR:** Add `$this->authorize('view', $lease)` in `DownloadLeaseController` for download and preview (or use route middleware `->can('view', 'lease')`).
2. **Landlord API IDOR:** Call `verifyLandlordOwnership($landlordId)` in `LandlordApprovalController::apiShow`, `apiApprove`, and `apiReject`.
3. **Staff import password:** Remove hardcoded `password123`; use config or random + force reset.
4. **Security headers:** Implement and register `SecurityHeaders` middleware as in SECURITY.md.

### Short-term (High)

5. Add feature tests for lease download authorization (after fix #1).
6. Add feature tests for landlord API authorization (after fix #2).
7. Add server-side MIME/extension allowlist (and optional magic-byte check) in `DocumentUploadService` or upload validation layer.
8. Document “test everything” and coverage in README or `docs/TESTING.md` (point to this guide).

### Medium-term (Improvements)

9. Extract PDF generation (cache key, watermark, template selection) into a `LeasePdfService`.
10. Centralize API error formatting and logging (exception handler or trait).
11. Add PHPUnit coverage reporting and optional minimum threshold in CI.
12. Consider cache tags for lease PDF cache invalidation.

### Long-term (Nice to have)

13. Broader test coverage for DocumentUploadService, TenantSigningController (signature/tenant checks), and Excel import.
14. Optional materialized view or extra indexes for heavy “expiring soon” / reporting queries.

---

## 8. Summary Table

| Pillar | Critical | High | Medium/Low |
|--------|----------|------|------------|
| **Code Quality** | 0 | 0 | 2 (error handling, PDF extraction) |
| **Security** | 3 (Lease IDOR, Landlord API IDOR, hardcoded password) | 1 (SecurityHeaders) | 1 (file validation) |
| **Testing** | 0 | 0 | Coverage gaps, add coverage config |
| **Performance** | 0 | 0 | Cache tags, monitor N+1 |

**Total critical:** 3 (all security). Addressing the four “Immediate” items above will materially improve security and align the app with the intended security model.
