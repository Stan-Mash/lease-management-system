# Financial Calculation Rules

- Always use `App\Helpers\Money` or `App\Support\MoneyHelper` for monetary arithmetic
- Never use native PHP float arithmetic for rent, deposits, arrears, or escalations
- `Money::escalate('50000.00', '5.5')` → `'52750.00'` (exact BCMath, no float rounding)
- `MoneyHelper::applyRate()` for escalation calculations
- Decimal columns in migrations: `$table->decimal('amount', 12, 2)`
- Never use `float` or `double` column types for monetary values
