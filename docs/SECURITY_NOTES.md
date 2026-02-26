# Security Reference — Chabrin Lease Management

## PII Encryption (CRITICAL — never remove casts)
Tenant and Landlord models use `'encrypted'` cast (AES-256-CBC) on:
- `national_id`, `passport_number`, `pin_number`

Applied 2026-02-23; 22,070 existing rows encrypted via `php artisan pii:encrypt`.
- **Never remove these casts** — raw ciphertext would show as gibberish in UI
- Adding a new sensitive field: add `'encrypted'` cast, then run `php artisan pii:encrypt --force`

## Content Security Policy
`SecurityHeaders` middleware generates a per-request nonce, shared with Blade as `$cspNonce`.

All inline `<script>` tags **must** include the nonce:
```html
<script nonce="{{ $cspNonce }}">
    // inline JS here
</script>
```
Without the nonce, inline scripts are blocked in production.

## Template Sanitizer
`TemplateSanitizer` blocks dangerous PHP functions in Blade lease templates.
- Validated at: form save (LeaseTemplateResource) AND render time (TemplateRenderService)
- Blocked: `system`, `exec`, `eval`, `file_get_contents`, `curl_*`, `unserialize`, `include`, etc.
- If a legitimate variable matches a blocked pattern, update the allowlist in `TemplateSanitizer::BLOCKED_PATTERNS`

## File Uploads (Tenant ID Documents)
- `finfo()` magic-byte validation (not just MIME headers)
- UUID filenames
- Stored at `storage/app/private/tenant-id-documents/{lease_uuid}/{uuid}.{ext}` — never web-accessible

## Database Backups
- Commands: `php artisan db:backup --compress`, `php artisan db:restore`
- Auth: temp `.pgpass` file (chmod 0600, deleted in `finally` block)
- **Never revert to `PGPASSWORD` env var**

## OTP Security
- Validity window: **15 minutes** (reduced from 30 to shrink replay window)
- Server-side expiry enforced in `OTPService::verify()` regardless of client timer
- Codes hashed with `Hash::make()` before storage — never stored plain-text

## Financial Security (see also FINANCIAL_POLICY.md)
- All monetary arithmetic via `App\Helpers\Money` or `App\Support\MoneyHelper` (BCMath)
- Never native PHP float arithmetic — precision errors in financial calculations
