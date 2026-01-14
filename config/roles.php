<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Roles Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all user roles and their configurations.
    | You can add, remove, or modify roles here.
    |
    */

    'roles' => [
        'super_admin' => [
            'name' => 'Super Admin',
            'description' => 'Full system access with all permissions',
            'color' => 'danger', // Badge color in Filament
            'permissions' => ['*'], // All permissions
        ],
        'admin' => [
            'name' => 'Admin',
            'description' => 'Administrative access to most features',
            'color' => 'warning',
            'permissions' => [
                'view_any_lease',
                'create_lease',
                'update_lease',
                'delete_lease',
                'view_any_user',
                'create_user',
                'update_user',
            ],
        ],
        'manager' => [
            'name' => 'Manager',
            'description' => 'Can manage leases and properties',
            'color' => 'info',
            'permissions' => [
                'view_any_lease',
                'create_lease',
                'update_lease',
                'view_any_property',
                'view_any_tenant',
            ],
        ],
        'staff' => [
            'name' => 'Staff',
            'description' => 'Basic staff access',
            'color' => 'success',
            'permissions' => [
                'view_any_lease',
                'view_any_property',
                'view_any_tenant',
            ],
        ],
        'agent' => [
            'name' => 'Agent',
            'description' => 'Property agent with lease management',
            'color' => 'primary',
            'permissions' => [
                'view_any_lease',
                'create_lease',
                'update_lease',
                'view_any_property',
                'view_any_tenant',
            ],
        ],
        'viewer' => [
            'name' => 'Viewer',
            'description' => 'Read-only access to view data',
            'color' => 'gray',
            'permissions' => [
                'view_any_lease',
                'view_any_property',
                'view_any_tenant',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Role
    |--------------------------------------------------------------------------
    |
    | The default role assigned to new users if none is specified.
    |
    */
    'default_role' => 'staff',

    /*
    |--------------------------------------------------------------------------
    | Role Hierarchy
    |--------------------------------------------------------------------------
    |
    | Define which roles can manage other roles.
    | Format: 'role' => ['roles_it_can_manage']
    |
    */
    'hierarchy' => [
        'super_admin' => ['super_admin', 'admin', 'manager', 'staff', 'agent', 'viewer'],
        'admin' => ['manager', 'staff', 'agent', 'viewer'],
        'manager' => ['staff', 'agent', 'viewer'],
    ],
];
