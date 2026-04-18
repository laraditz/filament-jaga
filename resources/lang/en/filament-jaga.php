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

    'tabs' => [
        'route_permissions'  => 'Route Permissions',
        'custom_permissions' => 'Custom Permissions',
        'wildcard_patterns'  => 'Wildcard Patterns',
        'ungrouped'          => 'Other',
        'wildcard_hint'      => 'Wildcard patterns grant access to any permission matching a glob-style pattern (e.g. reports.*). Adding * grants access to everything. Permissions above will reflect which routes are covered.',
        'all'                => 'All',
        'route'              => 'Route',
        'custom'             => 'Custom',
        'roles'              => 'Roles',
        'users'              => 'Users',
    ],

    'relation_managers' => [
        'roles' => [
            'attach_label' => 'Attach Role',
            'detach_label' => 'Detach',
        ],
        'users' => [
            'attach_label' => 'Attach User',
            'detach_label' => 'Detach',
        ],
    ],

    'actions' => [
        'assign_user'   => 'Assign User',
        'add_wildcard'  => 'Add Wildcard Pattern',
    ],

    'fields' => [
        'name'                => 'Name',
        'slug'                => 'Slug',
        'description'         => 'Description',
        'group'               => 'Group',
        'access_level'        => 'Access Level',
        'methods'             => 'HTTP Methods',
        'guard_name'          => 'Guard',
        'is_custom'           => 'Custom',
        'is_auto_description' => 'Auto Description',
        'wildcard_patterns'   => 'Wildcard Patterns',
        'permissions'         => 'Permissions',
        'custom_permissions'         => 'Custom Permissions',
        'select_all_permissions'     => 'Select All Permissions',
        'permissions_count'   => 'Permissions',
        'created_at'          => 'Created',
    ],

    'sync_permissions' => [
        'label'                => 'Sync Permissions',
        'modal_heading'        => 'Sync Permissions',
        'modal_description'    => 'This will discover new routes, update existing permissions, and soft-delete any stale route-based permissions. Custom permissions are never affected.',
        'success_notification' => 'Permissions sync has been queued.',
    ],

    'install' => [
        'enter_email'    => 'Enter the email of the user to assign the super-admin role',
        'user_not_found' => 'No user found with that email. Please try again.',
        'no_users'       => 'No users found. Run `php artisan jaga:install --assign` after creating your first user.',
        'success'        => 'filament-jaga installed successfully.',
        'role_assigned'  => 'Super-admin role assigned to :email.',
    ],
];
