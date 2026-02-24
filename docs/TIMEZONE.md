# Application Timezone

## Configuration

The application timezone is set in **`config/app.php`**:

```php
'timezone' => env('APP_TIMEZONE', 'Africa/Nairobi'),
```

- **Default:** `Africa/Nairobi` (Kenya) for lease dates, payment due dates, and reporting.
- **Override:** Set `APP_TIMEZONE` in `.env` if you deploy in another region (e.g. `UTC`).

---

## Usage

- Laravel’s `now()`, `today()`, and Carbon use this timezone automatically.
- Use the same timezone when comparing lease start/end dates, payment due dates, and approval timestamps.
- For user-facing date display, no extra conversion is needed as long as the app timezone matches the business region.

---

## Carbon Examples

All of these respect the application timezone (e.g. `Africa/Nairobi`):

```php
use Carbon\Carbon;

// Current date and time in app timezone
$now = now();                    // Carbon instance
$today = today();                // Start of today (00:00:00)
$todayString = now()->toDateString();  // 'Y-m-d'

// Lease date comparisons (e.g. in Lease model or services)
$lease->start_date->isPast();
$lease->end_date->isFuture();
$lease->end_date->diffInDays(now(), false);

// Start/end of day for reporting
$startOfDay = now()->startOfDay();
$endOfDay   = now()->endOfDay();

// Format for display (uses app timezone)
$lease->start_date->format('d/m/Y');           // 23/02/2026
$lease->start_date->format('F j, Y');          // February 23, 2026
$approval->reviewed_at->format('d M Y, H:i');  // 23 Feb 2026, 14:30
```

To use a **specific timezone** in a one-off (e.g. display in user’s zone):

```php
$inNairobi = $lease->start_date->timezone('Africa/Nairobi');
$inUtc     = $lease->start_date->utc();
```

---

## Database and Storage

- Laravel stores dates in the database as the application uses them; the app timezone is applied when reading/writing.
- Ensure `config('app.timezone')` is set correctly before running migrations or seeders so stored dates match the business region.

---

## Related Docs

- **`config/app.php`** — `timezone` and `locale`.
- **[FINANCIAL_POLICY.md](FINANCIAL_POLICY.md)** — Currency and rounding; date handling for payments.
- **[SYNC_AND_DEPLOY.md](SYNC_AND_DEPLOY.md)** — Deployment and environment (including server timezone if relevant).
