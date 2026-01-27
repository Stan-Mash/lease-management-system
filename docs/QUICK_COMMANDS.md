# Quick Reference Commands

## Initial Setup

```bash
# Update all composer dependencies
composer update

# Create necessary directories
mkdir -p storage/app/imports
mkdir -p storage/app/qrcodes

# Run migrations with new indexes and soft deletes
php artisan migrate

# Seed database with demo data
php artisan db:seed

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Testing

```bash
# Run all tests
php artisan test

# Run with verbose output
php artisan test --verbose

# Run specific test file
php artisan test tests/Feature/LeaseWorkflowTest.php
php artisan test tests/Unit/SerialNumberServiceTest.php

# Run tests in parallel
php artisan test --parallel

# Generate coverage report
./vendor/bin/phpunit --coverage-html coverage
```

## Development Server

```bash
# Start Laravel development server
php artisan serve

# Start with queue worker (async jobs)
composer run dev

# Or run separately
php artisan serve
php artisan queue:listen
```

## API Testing

```bash
# Verify a lease (public endpoint, rate limited to 10 req/min)
curl -X GET "http://localhost:8000/api/v1/leases/1/verify"

# Get all leases (requires auth token)
curl -X GET "http://localhost:8000/api/v1/leases" \
  -H "Authorization: Bearer YOUR_TOKEN"

# List properties
curl -X GET "http://localhost:8000/api/v1/properties" \
  -H "Authorization: Bearer YOUR_TOKEN"

# List landlords
curl -X GET "http://localhost:8000/api/v1/landlords" \
  -H "Authorization: Bearer YOUR_TOKEN"

# List tenants
curl -X GET "http://localhost:8000/api/v1/tenants" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Database Commands

```bash
# Check migration status
php artisan migrate:status

# Refresh migrations (WARNING: Deletes all data!)
php artisan migrate:refresh

# Create a new migration
php artisan make:migration migration_name

# Run specific migration
php artisan migrate --path=database/migrations/2026_01_13_200000_add_indexes_and_soft_deletes.php

# Rollback last migration
php artisan migrate:rollback

# Seed database
php artisan db:seed

# Seed specific seeder
php artisan db:seed --class=DatabaseSeeder
```

## Model & Factory Commands

```bash
# Create new model with migration
php artisan make:model ModelName -m

# Create factory for a model
php artisan make:factory ModelNameFactory

# Create test
php artisan make:test TestNameTest --feature

# Create unit test
php artisan make:test TestNameTest --unit
```

## Cache Commands

```bash
# Clear all cache
php artisan cache:clear

# Clear specific cache tag
php artisan cache:clear --tags=lease-stats

# Forget specific cache key
php artisan cache:forget lease-stats

# View cache config
cat config/cache.php
```

## Queue Commands

```bash
# Start queue worker
php artisan queue:work

# Start with specific queue
php artisan queue:work --queue=notifications

# Listen for jobs
php artisan queue:listen

# Retry failed jobs
php artisan queue:retry all

# View failed jobs
php artisan queue:failed
```

## Code Quality & Formatting

```bash
# Laravel Pint (code formatting - already installed)
./vendor/bin/pint

# Check formatting without fixing
./vendor/bin/pint --test

# Format specific file
./vendor/bin/pint app/Models/Lease.php
```

## Artisan Tinker (Interactive Shell)

```bash
# Open Tinker shell
php artisan tinker

# In Tinker - Test a lease transition
$lease = Lease::first();
$lease->transitionTo('approved');

# In Tinker - Create a token for API testing
User::first()->createToken('test-token')->plainTextToken

# In Tinker - Test scopes
Lease::active()->count()
Lease::pending()->count()
Lease::expiringSoon()->count()

# In Tinker - Clear a specific cache
Cache::forget('lease-stats')

# In Tinker - Check a tenant's active leases
$tenant = Tenant::first();
$tenant->activeLeaseCount()
```

## Environment Setup

```bash
# Copy example env
cp .env.example .env

# Generate app key
php artisan key:generate

# Create database (in .env set DB_DATABASE)
# Then run migrations
php artisan migrate

# Configure mail (in .env)
# MAIL_DRIVER=smtp
# MAIL_HOST=smtp.mailtrap.io
# MAIL_USERNAME=your_username
# MAIL_PASSWORD=your_password
```

## Route Commands

```bash
# List all routes
php artisan route:list

# List only API routes
php artisan route:list --path=api

# List only web routes
php artisan route:list --except-vendor

# Show route details
php artisan route:show
```

## Debug Commands

```bash
# Check for errors
php artisan list

# Show environment
php artisan env

# Show Laravel version
php artisan --version

# Show configuration
php artisan config:show

# Optimize autoloader
composer dump-autoload -o

# Clear compiled classes
php artisan optimize:clear
```

## Production Deployment

```bash
# Optimize for production
php artisan optimize

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Build frontend assets
npm run build

# Check security headers
php artisan security
```

## Troubleshooting

```bash
# Restart everything
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild autoloader
composer dump-autoload

# Clear all caches and restart
php artisan cache:clear && php artisan config:clear && php artisan route:clear

# If migrations fail
php artisan migrate:rollback
php artisan migrate:refresh
php artisan migrate

# If tests fail
php artisan config:clear
php artisan cache:clear
php artisan test
```

## Import Excel Data

```bash
# Using artisan command (create if doesn't exist)
php artisan import:landlords --file=landlords.xlsx
php artisan import:properties --file=properties.xlsx
php artisan import:units --file=units.xlsx
php artisan import:tenants --file=tenants.xlsx

# Or via seeder
php artisan db:seed --class=DatabaseSeeder
```

## Demo User Management

```bash
# Create admin user
php artisan tinker
User::create([
  'name' => 'Admin',
  'email' => 'admin@example.com',
  'password' => Hash::make('password'),
  'role' => 'super_admin'
])

# Reset demo user password
php artisan tinker
User::where('email', 'admin@chabrin.test')->update(['password' => Hash::make('password')])
```

## API Token Generation

```bash
# Generate personal access token
php artisan tinker
User::first()->createToken('api-token')->plainTextToken

# Revoke all tokens
User::first()->tokens()->delete()

# Check tokens
User::first()->tokens
```

## Common Issues & Solutions

```bash
# "SQLSTATE[HY000]: General error"
# Solution:
php artisan migrate:rollback
php artisan migrate

# "Class not found"
# Solution:
composer dump-autoload -o

# Port 8000 already in use
# Solution:
php artisan serve --port=8001

# Permission denied on storage
# Solution:
chmod -R 775 storage bootstrap/cache

# Cache not clearing
# Solution:
php artisan cache:clear --tags=lease-stats
php artisan cache:clear
```

## Performance Monitoring

```bash
# Monitor database queries
php artisan tinker
DB::enableQueryLog()
Lease::active()->get()
dd(DB::getQueryLog())

# Check index usage
# In MySQL:
# SHOW INDEX FROM leases;
# SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME='leases';

# Monitor memory usage
php artisan tinker
echo memory_get_usage() / 1024 / 1024 . " MB"
```

## File Permissions (Linux/Mac)

```bash
# Set correct permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chmod -R 775 public

# Set ownership
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

## Development Tips

```bash
# Use Log in code
use Illuminate\Support\Facades\Log;

Log::info('Message', $data);
Log::debug('Debug message');
Log::error('Error message', $exception);

# View logs
tail -f storage/logs/laravel.log

# Use dd() for debugging
dd($variable);

# Use dump() to display without stopping
dump($variable);

# Test email locally
# Install: composer require symfony/var-dumper
# Check: storage/logs/laravel.log for email details
```

---

**Save this file for quick reference during development!**
