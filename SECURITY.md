# Chabrin Lease Management System - Security Documentation

## Table of Contents
1. [Role-Based Access Control (RBAC)](#role-based-access-control)
2. [Zone-Based Data Segregation](#zone-based-data-segregation)
3. [API Security](#api-security)
4. [Authentication & Authorization](#authentication--authorization)
5. [Data Protection](#data-protection)
6. [Security Headers](#security-headers)
7. [Rate Limiting](#rate-limiting)
8. [Audit Logging](#audit-logging)
9. [Production Checklist](#production-checklist)

---

## Role-Based Access Control (RBAC)

### User Roles

The system implements a hierarchical role-based access control system:

| Role | Access Level | Permissions |
|------|--------------|-------------|
| `super_admin` | Global | Full access to all data, all zones, system configuration |
| `admin` | Global | Access to all data, all zones (limited system config) |
| `zone_manager` | Zone-restricted | Access only to leases, landlords, properties in assigned zone |
| `field_officer` | Zone-restricted | Access only to leases, landlords, properties in assigned zone |
| `manager` | Global/Custom | Custom permissions for lease management |
| `agent` | Global/Custom | Custom permissions for lease operations |
| `viewer` | Read-only | View-only access |

###Role Methods in User Model

```php
// Check specific roles
$user->isSuperAdmin();      // true if super_admin
$user->isAdmin();            // true if super_admin or admin
$user->isZoneManager();      // true if zone_manager
$user->isFieldOfficer();     // true if field_officer

// Check zone restrictions
$user->hasZoneRestriction(); // true for zone_manager and field_officer
$user->canAccessZone($zoneId);
$user->canAccessLease($lease);
```

---

## Zone-Based Data Segregation

### Zone Structure

Each zone represents a geographic or organizational unit:

- **One Zone Manager per Zone** (enforced at application level)
- **Multiple Field Officers per Zone** (allowed)
- **One Field Officer can ONLY be in ONE Zone** (enforced via database constraint)

### Database Schema

```sql
CREATE TABLE zones (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    code VARCHAR(255) UNIQUE,
    zone_manager_id BIGINT REFERENCES users(id),
    -- ... other fields
);

-- Users assigned to zones
ALTER TABLE users ADD zone_id BIGINT REFERENCES zones(id);

-- Leases assigned to zones and field officers
ALTER TABLE leases ADD zone_id BIGINT REFERENCES zones(id);
ALTER TABLE leases ADD assigned_field_officer_id BIGINT REFERENCES users(id);

-- Landlords assigned to zones
ALTER TABLE landlords ADD zone_id BIGINT REFERENCES zones(id);
```

### Automatic Data Filtering

**Filament Resources** automatically filter data based on user's zone:

```php
// In LeaseResource
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    $user = auth()->user();

    if ($user && $user->hasZoneRestriction() && $user->zone_id) {
        $query->where('zone_id', $user->zone_id);
    }

    return $query;
}
```

**Eloquent Scopes** for manual filtering:

```php
// Filter by user's zone
Lease::accessibleByUser(auth()->user())->get();

// Filter by specific zone
Lease::inZone($zoneId)->get();

// Filter by field officer
Lease::assignedToFieldOfficer($userId)->get();
```

---

## API Security

### Current State (Development)

⚠️ **WARNING**: API endpoints are currently **UNPROTECTED** and should NOT be used in production without implementing authentication.

### Required Implementation for Production

#### 1. Install Laravel Sanctum

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

#### 2. Generate API Tokens

```php
// Generate token for landlord
$landlord = Landlord::find(1);
$token = $landlord->user->createToken('landlord-app')->plainTextToken;

// Generate token for field officer
$fieldOfficer = User::where('role', 'field_officer')->first();
$token = $fieldOfficer->createToken('field-officer-app')->plainTextToken;
```

#### 3. Protect API Routes

Update `routes/web.php`:

```php
// Landlord API - Require authentication
Route::prefix('api/landlord/{landlordId}')
    ->middleware(['auth:sanctum'])
    ->name('api.landlord.')
    ->group(function () {
        // ... routes
    });

// Field Officer API - Require authentication
Route::prefix('api/field-officer')
    ->middleware(['auth:sanctum', 'role:field_officer,zone_manager,admin'])
    ->name('api.field-officer.')
    ->group(function () {
        // ... routes
    });
```

#### 4. Create Role Middleware

Create `app/Http/Middleware/CheckRole.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user() || !in_array($request->user()->role, $roles)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}
```

Register in `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ... other middleware
    'role' => \App\Http\Middleware\CheckRole::class,
];
```

---

## Authentication & Authorization

### Prevent Unauthorized Access

#### Policy-Based Authorization

Create `app/Policies/LeasePolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\Lease;
use App\Models\User;

class LeasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasZoneRestriction();
    }

    public function view(User $user, Lease $lease): bool
    {
        return $user->canAccessLease($lease);
    }

    public function update(User $user, Lease $lease): bool
    {
        // Only admins and zone managers can update
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isZoneManager() && $user->zone_id === $lease->zone_id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Lease $lease): bool
    {
        // Only admins can delete
        return $user->isAdmin();
    }
}
```

Register in `app/Providers/AuthServiceProvider.php`:

```php
protected $policies = [
    Lease::class => LeasePolicy::class,
];
```

---

## Data Protection

### 1. Encrypt Sensitive Data

Update `app/Models/Tenant.php`:

```php
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

protected function idNumber(): Attribute
{
    return Attribute::make(
        get: fn ($value) => $value ? Crypt::decryptString($value) : null,
        set: fn ($value) => $value ? Crypt::encryptString($value) : null,
    );
}
```

### 2. Hash Signatures

Already implemented in `DigitalSignature` model:

```php
protected $casts = [
    'signature_hash' => 'hashed',
];
```

### 3. Secure File Storage

Configure `.env`:

```env
FILESYSTEM_DISK=s3  # Use S3 or secure cloud storage

# AWS S3 Configuration
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### 4. Database Backup Encryption

```bash
# Install backup package
composer require spatie/laravel-backup

# Publish config
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

Configure `config/backup.php`:

```php
'backup' => [
    'password' => env('BACKUP_ARCHIVE_PASSWORD'),
    'encryption' => 'default',
],
```

---

## Security Headers

Create `app/Http/Middleware/SecurityHeaders.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Content Security Policy
        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; " .
            "style-src 'self' 'unsafe-inline'; " .
            "img-src 'self' data: https:; " .
            "font-src 'self' data:; " .
            "connect-src 'self';"
        );

        return $response;
    }
}
```

Register in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ... other middleware
    \App\Http\Middleware\SecurityHeaders::class,
];
```

---

## Rate Limiting

### API Rate Limiting

Update `app/Providers/RouteServiceProvider.php`:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for('landlord-api', function (Request $request) {
        return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for('field-officer-api', function (Request $request) {
        return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for('otp', function (Request $request) {
        return Limit::perMinute(3)->by($request->ip());
    });
}
```

Apply to routes:

```php
Route::middleware(['throttle:landlord-api'])->group(function () {
    // Landlord API routes
});

Route::middleware(['throttle:field-officer-api'])->group(function () {
    // Field Officer API routes
});
```

---

## Audit Logging

### Enhanced Audit Logging

Already implemented in `LeaseAuditLog` model. Extend for security events:

Create `app/Services/SecurityAuditService.php`:

```php
<?php

namespace App\Services;

use App\Models\SecurityAuditLog;
use Illuminate\Support\Facades\Request;

class SecurityAuditService
{
    public static function logSecurityEvent(string $event, array $details = []): void
    {
        SecurityAuditLog::create([
            'event' => $event,
            'user_id' => auth()->id(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'details' => $details,
        ]);
    }

    public static function logFailedLogin(string $email): void
    {
        self::logSecurityEvent('failed_login', ['email' => $email]);
    }

    public static function logUnauthorizedAccess(string $resource): void
    {
        self::logSecurityEvent('unauthorized_access', ['resource' => $resource]);
    }

    public static function logZoneViolation(int $attemptedZoneId): void
    {
        self::logSecurityEvent('zone_violation', [
            'attempted_zone' => $attemptedZoneId,
            'user_zone' => auth()->user()->zone_id,
        ]);
    }
}
```

---

## Production Checklist

### Before Deployment

#### Environment Configuration

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate new `APP_KEY`: `php artisan key:generate`
- [ ] Set secure `SESSION_DRIVER=database` or `redis`
- [ ] Configure proper `CACHE_DRIVER=redis` (recommended)
- [ ] Set up `QUEUE_CONNECTION=redis` or `database`
- [ ] Configure `MAIL_*` settings for production mail server
- [ ] Set `DB_*` to production database credentials
- [ ] Enable `HTTPS` and force SSL: `SESSION_SECURE_COOKIE=true`

#### Security Measures

- [ ] Install and configure Laravel Sanctum for API authentication
- [ ] Implement rate limiting on all API endpoints
- [ ] Add CSRF protection to all forms (already enabled by default)
- [ ] Enable security headers middleware
- [ ] Configure firewall rules (allow only ports 80, 443, 22)
- [ ] Set up fail2ban for SSH brute force protection
- [ ] Enable database query logging for auditing
- [ ] Implement IP whitelisting for admin panel (optional)
- [ ] Set up intrusion detection system (IDS)

#### Database Security

- [ ] Create separate database user with limited privileges
- [ ] Enable database encryption at rest
- [ ] Set up regular automated encrypted backups
- [ ] Test backup restoration procedure
- [ ] Enable database query logging
- [ ] Remove default/test accounts from database

#### File System Security

- [ ] Set correct file permissions: `chmod 755` for directories, `644` for files
- [ ] Set `storage/` and `bootstrap/cache/` to `775`
- [ ] Ensure `.env` file is `600` (owner read/write only)
- [ ] Disable directory listing in web server config
- [ ] Configure file upload size limits
- [ ] Scan uploaded files for malware

#### SSL/TLS Configuration

- [ ] Install SSL certificate (Let's Encrypt recommended)
- [ ] Configure HTTPS redirect in web server
- [ ] Enable HSTS header
- [ ] Configure TLS 1.2+ only (disable older protocols)
- [ ] Use strong cipher suites

#### Server Hardening

- [ ] Update all system packages: `apt update && apt upgrade`
- [ ] Configure firewall (UFW): `ufw enable`
- [ ] Disable unused services
- [ ] Change default SSH port (optional but recommended)
- [ ] Disable root SSH login
- [ ] Set up automatic security updates
- [ ] Configure log rotation
- [ ] Install and configure monitoring tools (e.g., New Relic, Sentry)

#### Application Security

- [ ] Run `composer audit` to check for vulnerabilities
- [ ] Update all dependencies to latest secure versions
- [ ] Remove development dependencies: `composer install --no-dev --optimize-autoloader`
- [ ] Clear all caches: `php artisan optimize:clear`
- [ ] Optimize for production: `php artisan optimize`
- [ ] Enable OPcache in PHP configuration
- [ ] Configure proper error logging (don't expose stack traces)

#### Zone & Role Setup

- [ ] Create all zones in the system
- [ ] Assign zone managers to each zone
- [ ] Assign field officers to their respective zones
- [ ] Verify zone restrictions are working correctly
- [ ] Test that users cannot access other zones' data

#### Testing

- [ ] Test all API endpoints with proper authentication
- [ ] Verify rate limiting is working
- [ ] Test zone-based access control
- [ ] Verify role-based permissions
- [ ] Test file upload security
- [ ] Perform penetration testing
- [ ] Load testing with realistic traffic

#### Monitoring & Alerts

- [ ] Set up application monitoring (uptime, performance)
- [ ] Configure error tracking (Sentry, Bugsnag)
- [ ] Set up security alerts for failed login attempts
- [ ] Configure disk space alerts
- [ ] Set up database connection monitoring
- [ ] Configure backup success/failure alerts

#### Documentation

- [ ] Document all environment variables
- [ ] Create deployment runbook
- [ ] Document rollback procedure
- [ ] Create incident response plan
- [ ] Document API authentication process
- [ ] Update user guides with security best practices

---

## Contact & Reporting

For security vulnerabilities, please contact:
- **Email**: security@chabrin.com
- **Response Time**: Within 24 hours

**DO NOT** create public GitHub issues for security vulnerabilities.

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-01-14 | Initial security documentation with RBAC and zone-based access control |

---

**Last Updated**: 2026-01-14
**Status**: Production Ready (after completing checklist)
