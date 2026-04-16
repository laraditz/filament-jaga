<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    | Controls the navigation group label, icon, and sort order in Filament.
    */
    'navigation' => [
        'group' => 'Roles & Permissions',
        'icon'  => 'heroicon-o-shield-check',
        'sort'  => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    | Toggle individual resources on/off. Disabled resources are not registered
    | with the panel at all.
    */
    'resources' => [
        'roles'       => true,
        'permissions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission
    |--------------------------------------------------------------------------
    | The jaga permission name required to access any page in this plugin.
    | Run `php artisan jaga:install` to seed this permission.
    */
    'permission' => 'jaga.access',

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    | Used for the "users with roles" stat and the RoleAssignment component.
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Dashboard Slug
    |--------------------------------------------------------------------------
    | The URL segment for the plugin's dashboard page.
    */
    'dashboard_slug' => 'jaga',
];
