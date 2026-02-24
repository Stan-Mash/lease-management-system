# Financial Policy — Currency and Rounding

## Overview

The Chabrin Lease Management System handles monetary values for rent, deposits, arrears, and related amounts. This document defines how currency is stored, displayed, and calculated to ensure consistency and avoid rounding errors.

## Current Conventions

### Storage

- **Database:** Amounts are stored as `DECIMAL(12, 2)` (e.g. `monthly_rent`, `deposit_amount`, `guarantee_amount`).
- **Model casts:** Lease, Tenant, Unit, and related models use `'decimal:2'` for monetary attributes.
- **Default currency:** KES (Kenyan Shillings) unless otherwise specified per lease.

### Display

- All user-facing monetary values are formatted to **2 decimal places** using `number_format($value, 2)`.
- Currency code (e.g. KES) is shown alongside amounts in notifications and UI.

### Calculations

- **Rounding:** Financial calculations (e.g. renewal rent, escalation) use **round to 2 decimal places** via PHP’s `round($value, 2)`.
- **Risk:** Floating-point arithmetic can introduce small errors over many operations (e.g. 0.1 + 0.2 ≠ 0.3 in binary). For critical paths (invoicing, arrears), consider the options below.

## Recommendations for Critical Paths

1. **Consistent rounding:** Use a single helper (e.g. `MoneyHelper::round()`) for all monetary calculations so behaviour is consistent.
2. **Integer cents (optional):** For new features or refactors, consider storing amounts in the smallest currency unit (cents/cents for KES) as integers and converting only for display. This avoids float rounding entirely.
3. **bcmath (optional):** For high-precision calculations (e.g. bulk arrears), use `bcadd` / `bcsub` / `bcmul` with scale 2 instead of native float operators.

## Helper

The application provides **`App\Support\MoneyHelper`** for consistent rounding and formatting:

- **`MoneyHelper::round($value)`** — Round to 2 decimal places (use for all monetary calculations).
- **`MoneyHelper::format($value)`** / **`formatWithCurrency($value, 'KES')`** — Display formatting.
- **bcmath methods** (require PHP `bcmath` extension) for high-precision arithmetic:
  - **`MoneyHelper::add($a, $b)`**, **`sub($a, $b)`**, **`mul($a, $b)`**, **`div($a, $b)`** — Return string with 2 decimal places.
  - **`MoneyHelper::applyRate($amount, $rate)`** — Apply a decimal rate (e.g. `0.10` for 10% escalation): `amount * (1 + rate)`.

Use `MoneyHelper` when adding or changing financial logic.

## Timezone

Lease and payment dates use the application timezone (see `config/app.php` and [SYNC_AND_DEPLOY.md](SYNC_AND_DEPLOY.md)). Ensure all date comparisons and display use the same timezone (e.g. `Africa/Nairobi`) for rent due dates and reporting.
