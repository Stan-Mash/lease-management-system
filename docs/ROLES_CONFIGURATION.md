# Roles Configuration Guide

This document explains how to configure and manage user roles in the Chabrin Lease System.

## Configuration File

All roles are configured in: **`config/roles.php`**

This is the central location for managing all aspects of roles, including:
- Role names and descriptions
- Badge colors in the UI
- Default role for new users
- Role hierarchy (who can manage whom)
- Future permissions (when fully implemented)

## How to Add a New Role

1. Open `config/roles.php`
2. Add a new entry in the `'roles'` array:

```php
'your_role_key' => [
    'name' => 'Display Name',
    'description' => 'What this role can do',
    'color' => 'primary', // Badge color: danger, warning, info, success, primary, gray
    'permissions' => [
        'view_any_lease',
        'create_lease',
        // Add specific permissions
    ],
],
```

3. Save the file
4. Clear the cache: `php artisan config:clear`

## Available Badge Colors

- `danger` - Red (used for Super Admin)
- `warning` - Orange/Yellow (used for Admin)
- `info` - Blue (used for Manager)
- `success` - Green (used for Staff)
- `primary` - Brand color (used for Agent)
- `gray` - Gray (used for Viewer)

## Current Roles

### 1. **Super Admin** (super_admin)
- Full system access
- Can manage all users including other super admins
- Color: Red (danger)

### 2. **Admin** (admin)
- Administrative access to most features
- Can manage managers, staff, agents, and viewers
- Color: Orange (warning)

### 3. **Manager** (manager)
- Can manage leases and properties
- Can manage staff, agents, and viewers
- Color: Blue (info)

### 4. **Staff** (staff)
- Basic staff access
- View-only for most features
- Color: Green (success)
- **This is the default role**

### 5. **Agent** (agent)
- Property agent with lease management
- Can create and update leases
- Color: Primary blue

### 6. **Viewer** (viewer)
- Read-only access
- Cannot create or modify anything
- Color: Gray

## Role Hierarchy

The hierarchy determines which roles can create/edit users with other roles:

```php
'hierarchy' => [
    'super_admin' => ['super_admin', 'admin', 'manager', 'staff', 'agent', 'viewer'],
    'admin' => ['manager', 'staff', 'agent', 'viewer'],
    'manager' => ['staff', 'agent', 'viewer'],
],
```

**Example:** An Admin can create/edit users with Manager, Staff, Agent, or Viewer roles, but NOT Super Admin.

## Changing the Default Role

To change which role is assigned to new users by default:

1. Open `config/roles.php`
2. Find the `'default_role'` setting
3. Change it to your preferred role key:

```php
'default_role' => 'agent', // Changed from 'staff' to 'agent'
```

## How to Remove a Role

1. Open `config/roles.php`
2. Remove the role entry from the `'roles'` array
3. Update the `'hierarchy'` array if needed
4. Save and clear cache: `php artisan config:clear`

**Warning:** Make sure no users are currently assigned this role before removing it!

## Renaming a Role

To change the display name of a role:

1. Open `config/roles.php`
2. Find the role you want to rename
3. Update the `'name'` value:

```php
'staff' => [
    'name' => 'Employee', // Changed from 'Staff'
    // ... rest stays the same
],
```

4. Save and clear cache: `php artisan config:clear`

**Note:** The array key ('staff') should stay the same in the database!

## Using Roles in Code

The system provides a `RoleService` class for working with roles:

```php
use App\Services\RoleService;

// Get all roles
$roles = RoleService::getRoles();

// Get role options for dropdowns
$options = RoleService::getRoleOptions();

// Get a role's display name
$name = RoleService::getRoleName('super_admin'); // Returns "Super Admin"

// Get a role's color
$color = RoleService::getRoleColor('admin'); // Returns "warning"

// Check if a role exists
if (RoleService::roleExists('manager')) {
    // Do something
}

// Get roles a user can manage
$managedRoles = RoleService::getManagedRoles('admin');

// Check if one role can manage another
if (RoleService::canManageRole('admin', 'staff')) {
    // Admin can manage staff
}
```

## Best Practices

1. **Always use the config file** - Don't hardcode roles in your code
2. **Keep role keys consistent** - Once a role is in the database, don't change its key
3. **Update hierarchy when adding roles** - Make sure new roles fit into the management structure
4. **Clear cache after changes** - Run `php artisan config:clear` after editing roles
5. **Test thoroughly** - Verify role changes work correctly before deploying

## Troubleshooting

### Role dropdown is empty
- Clear config cache: `php artisan config:clear`
- Check `config/roles.php` syntax for errors

### Role colors not showing
- Make sure the color value is one of the valid Filament colors
- Clear cache: `php artisan optimize:clear`

### Can't manage certain users
- Check the role hierarchy in `config/roles.php`
- Ensure your role has permission to manage the target role

### New role not appearing
- Clear config cache: `php artisan config:clear`
- Clear all caches: `php artisan optimize:clear`
- Restart your development server

## Future Enhancements

The permissions system is prepared for:
- Granular permission controls
- Integration with Spatie Laravel Permission package
- Custom permission definitions per module
- Resource-level access control

For questions or issues, please refer to the main documentation or contact the development team.
