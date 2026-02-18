# Dashboard "Error while loading page" — Troubleshooting

## What was changed

- **Company Dashboard** and **System Pulse** widgets now catch exceptions and show fallback content instead of breaking the whole page.
- If a widget fails, it will show "Unable to load" or "No data" and the **real error is logged** on the server.

## Find the real error on the server

After deploying, if you still see "Error while loading page" or a widget shows "Unable to load", check the Laravel log:

```bash
# On the server (SSH into chips-leases-app-01)
cd /var/www/chips
tail -100 storage/logs/laravel.log
```

Search for lines like:

- `LeaseStatsWidget failed`
- `RevenueChartWidget failed`
- `LeaseStatusChartWidget failed`
- `ZonePerformanceWidget failed`
- `SystemPulse getViewData failed`

The log line will include the exception message so you can fix the root cause (e.g. missing table, wrong column name).

## Common causes after deploy

1. **Missing `jobs` or `failed_jobs` table**  
   If `QUEUE_CONNECTION=database` and migrations weren’t run:
   ```bash
   php artisan queue:table
   php artisan queue:failed-table
   php artisan migrate
   ```

2. **PostgreSQL and GROUP BY**  
   Revenue chart now uses a raw expression in `groupBy()` for PostgreSQL compatibility.

3. **Schema mismatch**  
   If the server DB is behind (e.g. missing `zone_id` on a table), run:
   ```bash
   php artisan migrate --force
   ```

4. **Cache**  
   After fixing config or code:
   ```bash
   php artisan optimize:clear
   ```

## Deploy these fixes

Commit and push the widget changes, then on the server:

```bash
cd /var/www/chips
git pull origin main
php artisan optimize:clear
```

No new migrations are required for the try-catch changes.
