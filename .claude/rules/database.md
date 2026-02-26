# Database Rules

## CHIPS Column Names (always use in SQL queries)
| Model | Old (compat accessor only) | New (use in SQL) |
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

Old names have backward-compat accessors on models — safe in PHP code, **not in raw SQL**.

## Indexes
- Always add indexes on foreign key columns
- Add indexes on columns used in WHERE clauses frequently
- Composite indexes for multi-column filters

## Connection (local machines)
- DB: `chabrin_leases`, user: `postgres`, password: `123`, port: 5432
