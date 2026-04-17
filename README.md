# Filament Jaga

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laraditz/filament-jaga.svg?style=flat-square)](https://packagist.org/packages/laraditz/filament-jaga)
[![Total Downloads](https://img.shields.io/packagist/dt/laraditz/filament-jaga.svg?style=flat-square)](https://packagist.org/packages/laraditz/filament-jaga)
[![License](https://img.shields.io/packagist/l/laraditz/filament-jaga?style=flat-square)](./LICENSE.md)

<p align="center"><img src="https://github.com/user-attachments/assets/9b1be32d-5a7f-4131-b8b7-58e06cd1f50f" alt="Filament Jaga Icon"></p>

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

Once installed, a **Roles & Permissions** group appears in the Filament sidebar with two resources:

### Roles

Full CRUD for roles:

- Create roles with a name and auto-generated slug
- Assign individual permissions via checkbox list grouped by permission group
- Add wildcard patterns (e.g. `posts.*`) for broad permission grants
- Edit or delete existing roles

### Permissions

Full management of permissions discovered by jaga and custom permissions you define:

- **Tabs** — switch between All, Route (auto-discovered), and Custom permissions
- **Grouping** — table rows grouped by permission group for easy scanning
- **Filters** — filter by access level (`public`, `auth`, `restricted`) or show soft-deleted records
- **Edit** — update the group, description, and access level of any permission
- **Create** — add custom permissions not tied to any route
- **Delete** — remove custom permissions (auto-discovered route permissions cannot be deleted)
- **Roles tab** — view, attach, and detach roles assigned to a permission
- **Users tab** — view, attach, and detach users assigned directly to a permission

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
    ->disableResource('permissions') // hide the Permissions resource
```

## Cache

Jaga caches permission data for performance. After making changes to permissions or roles, you can manage the cache with:

```bash
php artisan jaga:cache   # rebuild the permission cache
php artisan jaga:clear   # clear the permission cache
```

## Publishing Translations

```bash
php artisan vendor:publish --tag=filament-jaga-lang
```

Language files will be published to `lang/vendor/filament-jaga`.

## License

MIT
