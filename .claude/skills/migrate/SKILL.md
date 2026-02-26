---
name: migrate
description: Create and run a Laravel database migration for the Chabrin lease system
argument-hint: [migration-description]
user-invocable: true
allowed-tools: Bash(php artisan *), Write(*), Read(*)
---

# Create and Run Migration

Create a new migration for: $ARGUMENTS

## Steps

1. **Detect machine** from working directory:
   - `C:\Users\IT SUPPORT\...` → use `C:\Xampp\php\php.exe artisan`
   - Otherwise → use `php artisan`

2. **Create the migration:**
   ```bash
   php artisan make:migration $ARGUMENTS
   ```

3. **Write the migration** — follow these rules:
   - Use CHIPS column names (names, national_id, mobile_number, email_address, pin_number, property_name, reference_number, rent_amount)
   - Add indexes for foreign keys and frequently queried columns
   - Always provide both `up()` and `down()` methods
   - Use `bcmath`-compatible decimal columns for monetary values: `$table->decimal('amount', 12, 2)`

4. **Review** the migration before running

5. **Run the migration:**
   ```bash
   php artisan migrate
   ```

6. **Commit** the migration file immediately after successful run

## Notes
- DB: `chabrin_leases`, user: `postgres`, password: `123`, port: 5432
- Never use raw float columns for monetary values — use `decimal(12,2)`
