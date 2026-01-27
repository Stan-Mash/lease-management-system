# Authentication & Role-Based Access Control Guide

## System Overview

The Chabrin Lease Management System now has a complete user authentication and role-based access control system with Spatie Permission integration.

## Demo User Accounts

### 1. **Super Admin Account**
- **Email:** admin@chabrin.test
- **Password:** admin123
- **Role:** super_admin
- **Permissions:** Full system access, all features

### 2. **Manager Account**
- **Email:** manager@chabrin.test
- **Password:** manager123
- **Role:** manager
- **Permissions:** Lease management, reports, tenant management

### 3. **Agent Accounts** (3 users)
- **Email:** agent+[1-3]@chabrin.test
- **Password:** agent123
- **Role:** agent
- **Permissions:** View leases, create lease documents, limited tenant access

## How to Access the System

### Admin Panel Login
1. Navigate to `http://127.0.0.1:8000/admin`
2. You will be redirected to the login page
3. Enter your credentials (email and password from above)
4. Click "Sign in"

### After Login
- You'll see the admin dashboard with access to:
  - Leases (view, edit, create)
  - Tenants
  - Properties
  - Units
  - Landlords
  - User management (admin only)

## Role-Based Access Control

The system has 5 predefined roles:

| Role | Description | Access Level |
|------|-------------|--------------|
| **super_admin** | Full system access | All features, user management, reports |
| **admin** | Administrator | Lease management, reporting, configuration |
| **manager** | Department manager | Team leases, reports, approve operations |
| **agent** | Field agent | Own leases, customer interactions |
| **viewer** | Read-only access | View reports and dashboards only |

## Key Features Enabled

✅ **User Authentication**
- Email/password login
- Session management
- Remember me functionality
- Password reset (coming soon)

✅ **Role-Based Authorization**
- Spatie Permission integration
- Role-based middleware
- Resource-level access control
- Custom authorization policies

✅ **User Management** (Super Admin Only)
- Create/edit/delete users
- Assign roles
- Manage permissions
- View activity logs

✅ **Security Features**
- Password hashing (bcrypt)
- CSRF protection
- Session management
- Rate limiting on public routes

## User Model Integration

The `User` model now includes:

```php
use HasRoles; // From Spatie\Permission\Traits
```

This allows you to:
- Check user roles: `$user->hasRole('admin')`
- Check permissions: `$user->hasPermissionTo('edit leases')`
- Assign roles: `$user->assignRole('manager')`

## Database Schema

### Users Table Fields
- `id` - Primary key
- `name` - User's full name
- `email` - Email address (unique)
- `password` - Hashed password
- `role` - Primary role (super_admin, admin, manager, agent, viewer)
- `phone` - Phone number
- `avatar_path` - Profile picture
- `is_active` - Account status
- `last_login_at` - Last login timestamp
- `department` - Department assignment
- `bio` - User biography
- `timestamps` - created_at, updated_at

### Permissions Tables (Spatie Permission)
- `roles` - Role definitions
- `permissions` - Permission definitions
- `model_has_roles` - User-to-role relationships
- `model_has_permissions` - User-to-permission relationships
- `role_has_permissions` - Role-to-permission relationships

## Next Steps

1. **Customize Permissions** - Define specific permissions for your business logic
2. **Create Authorization Policies** - Control who can edit/delete specific resources
3. **Add Password Reset** - Implement email-based password recovery
4. **Two-Factor Authentication** - (Optional) Add 2FA for security
5. **Activity Logging** - Track user actions for compliance

## Troubleshooting

### Can't login with demo account?
- Ensure the database was seeded with `php artisan migrate:fresh --seed`
- Check that the email and password match exactly (case-sensitive)
- Verify the user is marked as `is_active = true`

### Seeing "Unauthenticated" error?
- You may need to login first
- Go to `/admin` to access the login page
- Use one of the demo accounts above

### Need to add a new user?
- Login as super_admin
- Navigate to Users section
- Click "New User"
- Fill in details and assign a role

## Security Notes

⚠️ **Important:**
- Change demo account passwords in production
- Never commit real passwords to version control
- Use environment variables for sensitive data
- Regularly audit user permissions
- Keep Laravel and dependencies updated

## Support

For questions or issues with authentication:
1. Check the `/admin` login page
2. Verify credentials match the demo accounts
3. Ensure database migrations have been run
4. Check Laravel logs in `storage/logs/`
