# Role-Based Access Control Implementation

## Overview

The Chabrin Lease Management System now has a complete role-based access control (RBAC) system built on:

1. **Spatie Permission** - For flexible role and permission management
2. **Laravel Built-in Auth** - For user authentication
3. **Filament Admin Panel** - For super admin user management
4. **Custom Middleware** - For route-level role protection

## Architecture

### 1. User Authentication Flow

```
1. User visits /admin
2. Filament middleware checks if authenticated
3. If not authenticated → redirect to /admin/login
4. User enters email/password
5. Laravel authenticates via Auth facade
6. If valid → create session & redirect to /admin dashboard
7. If invalid → show error & stay on login
```

### 2. Role System

Five predefined roles are available:

```php
'super_admin'  // Full system access - can manage users, roles, all leases
'admin'        // Administrative access - manage leases, reports, settings
'manager'      // Team lead - manage team's leases and operations
'agent'        // Field staff - manage own leases and customer interactions
'viewer'       // Read-only - view dashboards and reports only
```

### 3. Database Structure

#### Users Table Schema
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'manager', 'agent', 'viewer') DEFAULT 'agent',
    phone VARCHAR(255),
    avatar_path VARCHAR(255),
    is_active BOOLEAN DEFAULT true,
    last_login_at TIMESTAMP,
    department VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Spatie Permission Tables
```sql
roles                   -- Role definitions
permissions             -- Permission definitions
model_has_roles         -- User ↔ Role relationships
model_has_permissions   -- User ↔ Permission relationships
role_has_permissions    -- Role ↔ Permission relationships
```

## Implementation Details

### User Model Integration

```php
// app/Models/User.php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;  // ← NOW ENABLED
    
    public function hasRole($role): bool
    public function hasPermissionTo($permission): bool
    public function assignRole($role): void
    // ... and more
}
```

### Route Protection

#### Middleware-Based (Route Level)
```php
// routes/web.php

// Require authentication
Route::middleware(['auth'])->group(function () {
    Route::get('/leases/{lease}/download', DownloadLeaseController::class);
});

// Require specific roles
Route::middleware(['auth', 'role:super_admin,admin'])->group(function () {
    // Only super_admin and admin can access
});
```

#### Custom CheckRole Middleware
```php
// app/Http/Middleware/CheckRole.php
class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!in_array(auth()->user()->role, $roles)) {
            abort(403, 'Unauthorized');
        }
        return $next($request);
    }
}
```

### Filament Resource Protection

#### Super Admin Only Access
```php
// app/Filament/Resources/UserResource.php
class UserResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }
    
    // User management only visible to super_admin
}
```

#### Conditional Visibility
```php
// Show/hide forms based on user role
Forms\Components\Select::make('role')
    ->visible(fn () => auth()->user()->role === 'super_admin')
    ->required(),
```

## Authentication Flow Details

### Login Process

1. **User navigates to `/admin`**
   ```
   GET /admin → Filament Authenticate middleware
   ```

2. **No session found → Redirect to login**
   ```
   → GET /admin/login → Show login form
   ```

3. **User submits credentials**
   ```
   POST /admin/login → Laravel Auth::attempt()
   ```

4. **Authentication verification**
   ```php
   // Laravel checks:
   1. Email exists in users table
   2. Password matches (bcrypt hash comparison)
   3. is_active = true
   ```

5. **Session created on success**
   ```php
   // Laravel creates:
   1. Session ID in sessions table
   2. Sets LARAVEL_SESSION cookie
   3. Stores user ID in session
   ```

6. **Redirect to dashboard**
   ```
   → GET /admin → Show dashboard with user's role context
   ```

### Session Management

Sessions are stored in PostgreSQL:
```sql
SELECT * FROM sessions WHERE user_id = 1;
```

Middleware checks session on every request:
```
GET /admin/* → Authenticate middleware → Verify session exists → Check user is_active
```

## User Management in Admin Panel

### Super Admin Can:

1. **View all users**
   - Access `/admin/users` (UserResource page)
   - See user list with roles, status, last login

2. **Create new users**
   - Set name, email, password
   - Assign role (super_admin, admin, manager, agent, viewer)
   - Set department, phone, bio
   - Toggle active status

3. **Edit users**
   - Update all user fields
   - Change roles dynamically
   - Deactivate accounts

4. **Delete users**
   - Protected: Cannot delete the last super_admin account
   - Prevents accidental account lockout

### Agents/Managers See:
- Only their own profile (if feature added)
- Cannot access `/admin/users` resource
- No user management capability

## Security Features

### Password Security
```php
// Passwords are bcrypt hashed
$user->password = bcrypt('plain_text_password');

// Verification happens automatically
Auth::attempt(['email' => $email, 'password' => $password]);
```

### Session Security
```php
// CSRF protection on all forms
@csrf

// Session data encrypted
'SESSION_DRIVER' => 'database'

// Session timeout configured in config/session.php
'lifetime' => 120, // 120 minutes
```

### Rate Limiting
```php
// Public routes have rate limits
Route::get('/verify/lease', ...)->middleware('throttle:10,1');
// 10 requests per 1 minute
```

## Configuration Files

### bootstrap/app.php
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => \App\Http\Middleware\CheckRole::class,
    ]);
})
```

### config/auth.php
```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
],
```

## Demo Accounts

| Email | Password | Role |
|-------|----------|------|
| admin@chabrin.test | admin123 | super_admin |
| manager@chabrin.test | manager123 | manager |
| agent+1@chabrin.test | agent123 | agent |
| agent+2@chabrin.test | agent123 | agent |
| agent+3@chabrin.test | agent123 | agent |

## Troubleshooting

### Cannot Login
**Problem:** Login form appears but credentials don't work

**Solutions:**
1. Verify database was seeded: `php artisan migrate:fresh --seed`
2. Check user exists: `php artisan tinker` → `User::where('email', 'admin@chabrin.test')->first()`
3. Check is_active: `$user->is_active` should be `true`
4. Test password: `Hash::check('admin123', $user->password)`

### Unauthenticated Error
**Problem:** Getting "Unauthenticated" error on protected routes

**Solutions:**
1. You're not logged in - visit `/admin/login` first
2. Session expired - log in again
3. Check session driver: `config('session.driver')` should be 'database'

### User Cannot Access Resource
**Problem:** User sees 403 Unauthorized

**Solutions:**
1. Check user role: `User::find(1)->role`
2. Check middleware requirements in route
3. Check UserResource::canAccess() method
4. Verify role matches requirement

### Last Super Admin Cannot Be Deleted
**Problem:** Delete button shows but fails

**Solution:** This is by design. You need at least one super_admin. Create a new super_admin account before deleting old one.

## Next Steps

1. **Create Authorization Policies**
   ```php
   php artisan make:policy LeasePolicy --model=Lease
   ```

2. **Define Granular Permissions**
   - Setup specific permissions beyond roles
   - Implement policy checks on resources

3. **Add Password Reset**
   ```php
   php artisan make:mail PasswordReset
   ```

4. **Implement Activity Logging**
   - Track user actions
   - Audit user management changes

5. **Add Two-Factor Authentication**
   - Enhanced security for admin accounts
   - Consider packages like laravel-2fa

## Code Examples

### Check User Role in Code
```php
// In controller or service
if (auth()->user()->role !== 'super_admin') {
    abort(403, 'Only super admin can access this');
}

// More flexible
if (! in_array(auth()->user()->role, ['super_admin', 'admin'])) {
    abort(403);
}

// Using Spatie Permission
if (auth()->user()->cannot('manage users')) {
    abort(403);
}
```

### Get Logged-In User
```php
// Anywhere in application
auth()->user()           // Returns User instance or null
auth()->check()          // Returns boolean
auth()->id()             // Returns user ID or null
Auth::user()             // Same as auth()->user()
Auth::check()            // Same as auth()->check()
```

### Assign Role Programmatically
```php
$user = User::find(1);
$user->role = 'admin';
$user->save();

// Or if using Spatie Permission roles
$user->assignRole('admin');
```

### Update Last Login
```php
// Add to LoginController or use middleware
auth()->user()->update([
    'last_login_at' => now()
]);
```

## Files Modified/Created

1. ✅ `app/Models/User.php` - Enabled HasRoles trait
2. ✅ `database/seeders/DatabaseSeeder.php` - Added proper password hashing
3. ✅ `database/factories/TenantFactory.php` - Fixed column names
4. ✅ `app/Http/Middleware/CheckRole.php` - Created new middleware
5. ✅ `bootstrap/app.php` - Registered middleware alias
6. ✅ `routes/web.php` - Added role-based route protection
7. ✅ `app/Filament/Resources/UserResource.php` - Created user management resource
8. ✅ `app/Filament/Resources/Users/Pages/ListUsers.php` - User list page
9. ✅ `app/Filament/Resources/Users/Pages/CreateUser.php` - Create user page
10. ✅ `app/Filament/Resources/Users/Pages/EditUser.php` - Edit user page

## Testing Checklist

- [ ] Can login with admin@chabrin.test / admin123
- [ ] Can login with manager@chabrin.test / manager123
- [ ] Can login with agent+1@chabrin.test / agent123
- [ ] Super admin can see Users menu
- [ ] Super admin can create new user
- [ ] Super admin can edit user role
- [ ] Super admin can delete user (not last super_admin)
- [ ] Managers cannot see Users menu
- [ ] Agents cannot see Users menu
- [ ] Session expires after inactivity
- [ ] Cannot access `/admin/users` without super_admin role
- [ ] Logout works correctly
