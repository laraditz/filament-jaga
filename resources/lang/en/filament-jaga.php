<?php

return [
    'navigation' => [
        'group' => 'Roles & Permissions',
    ],

    'resources' => [
        'roles' => [
            'label'        => 'Role',
            'plural_label' => 'Roles',
        ],
        'permissions' => [
            'label'        => 'Permission',
            'plural_label' => 'Permissions',
        ],
    ],

    'pages' => [
        'dashboard' => [
            'title' => 'Roles & Permissions Overview',
        ],
    ],

    'widgets' => [
        'stats' => [
            'total_roles'       => 'Total Roles',
            'total_permissions' => 'Total Permissions',
            'users_with_roles'  => 'Users with Roles',
        ],
        'recent_roles' => [
            'heading' => 'Recently Added Roles',
        ],
        'permissions_by_group' => [
            'heading' => 'Permissions by Group',
        ],
    ],

    'actions' => [
        'assign_user' => 'Assign User',
    ],

    'fields' => [
        'name'                => 'Name',
        'slug'                => 'Slug',
        'description'         => 'Description',
        'group'               => 'Group',
        'access_level'        => 'Access Level',
        'methods'             => 'HTTP Methods',
        'is_custom'           => 'Custom',
        'is_auto_description' => 'Auto Description',
        'wildcard_patterns'   => 'Wildcard Patterns',
        'permissions'         => 'Permissions',
        'custom_permissions'  => 'Custom Permissions',
        'permissions_count'   => 'Permissions',
        'created_at'          => 'Created',
    ],

    'install' => [
        'enter_email'    => 'Enter the email of the user to assign the super-admin role',
        'user_not_found' => 'No user found with that email. Please try again.',
        'no_users'       => 'No users found. Run `php artisan jaga:install --assign` after creating your first user.',
        'success'        => 'filament-jaga installed successfully.',
        'role_assigned'  => 'Super-admin role assigned to :email.',
    ],
];
