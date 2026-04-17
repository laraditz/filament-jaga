# Filament Jaga

A [FilamentPHP v5](https://filamentphp.com) plugin for managing roles and permissions powered by [laraditz/jaga](https://github.com/laraditz/jaga).

## Requirements

| Dependency    | Version |
| ------------- | ------- |
| PHP           | ^8.2    |
| Laravel       | ^13.0   |
| Filament      | ^5.0    |
| laraditz/jaga | ^1.0    |

## Installation

Install via Composer:

```bash
composer require laraditz/filament-jaga
```

### 1. Set up the User model

Add the `HasRoles` trait to your `User` model:

```php
use Laraditz\Jaga\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

### 2. Register the plugin

Add `FilamentJagaPlugin` to your Filament panel provider:

```php
use Laraditz\FilamentJaga\FilamentJagaPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(FilamentJagaPlugin::make());
}
```

### 3. Run the installer

```bash
php artisan jaga:install
```

This will:

- Publish the config file
- Seed the `jaga.access` permission and `super-admin` role
- Assign the super-admin role to a user of your choice

To assign the role to an existing user non-interactively:

```bash
php artisan jaga:install --email=admin@example.com
```

To re-assign the role without re-seeding:

```bash
php artisan jaga:install --assign --email=admin@example.com
```

## Features

Once installed, a **Roles & Permissions** group appears in the Filament sidebar with three items:

### Overview

A dashboard showing at a glance:

- Total roles
- Total permissions
- Users with roles assigned

### Roles

Full CRUD for roles:

- Create roles with a name and auto-generated slug
- Assign individual permissions via checkbox list
- Add wildcard patterns (e.g. `posts.*`) for broad permission grants
- Edit or delete existing roles

### Permissions

Read-only list of all permissions discovered by jaga:

- Filter by access level (`public`, `auth`, `restricted`)
- Filter by custom vs. auto-discovered
- Grouped and sortable by group name

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=filament-jaga-config
```

`config/filament-jaga.php`:

```php
return [
    'navigation' => [
        'group' => 'Roles & Permissions', // sidebar group label
        'icon'  => 'heroicon-o-shield-check',
        'sort'  => 10,
    ],

    'resources' => [
        'roles'       => true, // set false to hide the Roles resource
        'permissions' => true, // set false to hide the Permissions resource
    ],

    // Permission required to access any page in this plugin
    'permission' => 'jaga.access',

    // Your app's User model
    'user_model' => \App\Models\User::class,

    // URL segment for the dashboard page (/your-panel/jaga)
    'dashboard_slug' => 'jaga',
];
```

## Customising the Plugin

All options are available via a fluent API:

```php
FilamentJagaPlugin::make()
    ->navigationGroup('Access Control')
    ->navigationIcon('heroicon-o-lock-closed')
    ->navigationSort(5)
    ->permission('admin.access')
    ->userModel(\App\Models\Admin::class)
    ->dashboardSlug('access')
    ->disableResource('permissions') // hide the Permissions resource
```

## Publishing Translations

```bash
php artisan vendor:publish --tag=filament-jaga-lang
```

Language files will be published to `lang/vendor/filament-jaga`.

## License

MIT
