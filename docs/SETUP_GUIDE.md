# Installation & Setup Guide

After implementing all code improvements, follow these steps:

## 1. Update Dependencies
```bash
composer update
```

## 2. Run Migrations
```bash
php artisan migrate
php artisan migrate --path=database/migrations/2026_01_13_200000_add_indexes_and_soft_deletes.php
```

## 3. Seed Database
```bash
# Create demo users and sample data
php artisan db:seed
```

## 4. Create Required Directories
```bash
mkdir -p storage/app/imports
mkdir -p storage/app/qrcodes
```

## 5. Configure Cache (Optional but Recommended)
```bash
# Install Redis if you want caching
# On Windows (via Docker): docker run -d -p 6379:6379 redis

# Update .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
```

## 6. Test the Installation
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Unit/SerialNumberServiceTest.php
php artisan test tests/Feature/LeaseWorkflowTest.php
```

## 7. Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## 8. Start Development Server
```bash
php artisan serve
# Access: http://localhost:8000

# Or use the dev script from composer.json:
composer run dev
```

## Testing API Endpoints
```bash
# Verify a lease
curl -X GET "http://localhost:8000/api/v1/leases/{id}/verify" \
  -H "Content-Type: application/json"

# Get all leases (requires auth)
curl -X GET "http://localhost:8000/api/v1/leases" \
  -H "Authorization: Bearer {token}"
```

## Demo Users
- **Admin**: admin@chabrin.test (role: super_admin)
- **Manager**: manager@chabrin.test (role: manager)
- All passwords default to: `password`

## What Was Implemented

✅ **AppServiceProvider** - Singleton services, model observers, production settings
✅ **Custom Exceptions** - InvalidLeaseTransition, LeaseVerification, SerialGeneration
✅ **Form Validation** - StoreTenant, UpdateTenant, StoreLandlord, StoreLease requests
✅ **Comprehensive Tests** - 10+ unit and feature tests for core functionality
✅ **Notifications** - Complete LeaseStateChanged with multi-channel support
✅ **Model Improvements** - Scopes (active, pending, expiring) and helper methods
✅ **Database Indexes** - Performance optimization on frequently queried fields
✅ **Soft Deletes** - Audit trail support for all main entities
✅ **Caching** - Dashboard stats cached for 1 hour
✅ **API Structure** - RESTful API with rate limiting and Sanctum auth
✅ **Security** - Rate limiting on public endpoints, input validation
✅ **Database Seeder** - Demo users, sample leases, and Excel import support
✅ **Lease Observer** - Automatic cache invalidation on model changes

## Running Tests

```bash
# Run all tests
php artisan test

# Run with coverage report
./vendor/bin/phpunit --coverage-html coverage

# Run specific suite
php artisan test tests/Feature
php artisan test tests/Unit

# Watch mode (requires phpunit-watch)
php artisan test --watch
```

## Next Steps

1. **Install Additional Packages** (optional):
   ```bash
   composer require spatie/laravel-activity-log
   composer require laravel/telescope --dev
   ```

2. **Configure Email** - Update `.env` for LeaseStateChanged notifications:
   ```
   MAIL_DRIVER=smtp
   MAIL_HOST=smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=your_username
   MAIL_PASSWORD=your_password
   ```

3. **Setup Queuing** - Run jobs asynchronously:
   ```bash
   php artisan queue:work
   ```

4. **Enable CORS** (if needed):
   ```bash
   composer require fruitcake/laravel-cors
   ```

5. **API Documentation** - Generate with Scribe:
   ```bash
   composer require knuckleswtf/scribe
   php artisan scribe:generate
   ```

## Troubleshooting

**Migrate errors?**
```bash
php artisan migrate:refresh
php artisan migrate
```

**Tests failing?**
```bash
php artisan config:clear
php artisan cache:clear
php artisan test --parallel
```

**Cache issues?**
```bash
php artisan cache:clear
php artisan config:cache
```

**API auth failing?**
```bash
php artisan tinker
# Then in tinker:
User::first()->createToken('test-token')->plainTextToken;
```

## Key Files Modified/Created

### Models
- [app/Models/Lease.php](app/Models/Lease.php) - Added scopes, soft deletes
- [app/Models/Landlord.php](app/Models/Landlord.php) - Added helper methods
- [app/Models/Tenant.php](app/Models/Tenant.php) - Added scopes and helpers
- [app/Models/Property.php](app/Models/Property.php) - Added occupancy calculations
- [app/Models/Unit.php](app/Models/Unit.php) - Added soft deletes

### Services & Controllers
- [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php) - Configuration
- [app/Observers/LeaseObserver.php](app/Observers/LeaseObserver.php) - Cache invalidation
- [app/Http/Controllers/Api/](app/Http/Controllers/Api/) - API controllers
- [app/Http/Requests/](app/Http/Requests/) - Form validation

### Tests
- [tests/Unit/SerialNumberServiceTest.php](tests/Unit/SerialNumberServiceTest.php)
- [tests/Unit/QRCodeServiceTest.php](tests/Unit/QRCodeServiceTest.php)
- [tests/Feature/LeaseWorkflowTest.php](tests/Feature/LeaseWorkflowTest.php)
- [tests/Feature/LeaseVerificationTest.php](tests/Feature/LeaseVerificationTest.php)

### Migrations
- [database/migrations/2026_01_13_200000_add_indexes_and_soft_deletes.php](database/migrations/2026_01_13_200000_add_indexes_and_soft_deletes.php)

### Routes
- [routes/api.php](routes/api.php) - RESTful API
- [routes/web.php](routes/web.php) - Updated with rate limiting
