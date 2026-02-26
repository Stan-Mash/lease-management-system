# Security Rules

## PII
- Tenant/Landlord `national_id`, `passport_number`, `pin_number` must have `'encrypted'` cast
- Never remove these casts — raw ciphertext would appear as gibberish
- New sensitive fields: add cast + run `php artisan pii:encrypt --force`

## CSP
- All inline `<script>` tags must include `nonce="{{ $cspNonce }}"`
- Without nonce, scripts are blocked in production

## OTP
- 15-minute validity window (enforced server-side in `OTPService::verify()`)
- Codes stored as `Hash::make()` — never plain-text

## File Uploads
- Use `finfo()` magic-byte validation (not just MIME headers)
- UUID filenames, store in `storage/app/private/` — never web-accessible

## Database Backups
- Use `.pgpass` temp file (chmod 0600, deleted in `finally`)
- Never use `PGPASSWORD` env var
