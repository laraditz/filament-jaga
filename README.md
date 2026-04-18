# Filament Jaga

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laraditz/filament-jaga.svg?style=flat-square)](https://packagist.org/packages/laraditz/filament-jaga)
[![Total Downloads](https://img.shields.io/packagist/dt/laraditz/filament-jaga.svg?style=flat-square)](https://packagist.org/packages/laraditz/filament-jaga)
[![License](https://img.shields.io/packagist/l/laraditz/filament-jaga?style=flat-square)](./LICENSE.md)

<p align="center"><img src="https://raw.githubusercontent.com/free-whiteboard-online/Free-Erasorio-Alternative-for-Collaborative-Design/49149e3c027039e5e04d365691a002a26c6cf466/uploads/2026-04-18T03-57-46-852Z-32gh15jba.png" alt="Filament Jaga" width="130"></p>

A **FilamentPHP v5** plugin for managing roles and permissions, powered by [laraditz/jaga](https://github.com/laraditz/jaga). Simple to set up. Easy to extend.

## 📸 Preview

<p align="center"><img src="https://raw.githubusercontent.com/free-whiteboard-online/Free-Erasorio-Alternative-for-Collaborative-Design/edd63bc33759e665b99394754340edc4f1a279e0/uploads/2026-04-17T13-56-24-719Z-3u4uyd235.png" alt="Filament Jaga Preview"></p>

## ✨ Features

### 🎭 Roles

- Create roles with a name and auto-generated slug
- Assign permissions via checkbox list grouped by permission group
- Add wildcard patterns (e.g. `posts.*`) for broad permission grants
- Edit or delete existing roles

### 🔑 Permissions

- **Tabs** — switch between All, Route (auto-discovered), and Custom permissions
- **Grouping** — table rows grouped by permission group for easy scanning
- **Filters** — filter by access level (`public`, `auth`, `restricted`) or show soft-deleted records
- **Edit** — update the group, description, and access level of any permission
- **Create** — add custom permissions not tied to any route
- **Delete** — remove custom permissions (auto-discovered route permissions cannot be deleted)
- **Roles tab** — view, attach, and detach roles assigned to a permission
- **Users tab** — view, attach, and detach users assigned directly to a permission

## 📋 Requirements

| Dependency    | Version |
| ------------- | ------- |
| PHP           | ^8.2    |
| Laravel       | ^13.0   |
| Filament      | ^5.0    |
| laraditz/jaga | ^1.0    |

## 🚀 Installation

**1.** Install via Composer:

```bash
composer require laraditz/filament-jaga
```

**2.** Publish the migrations and run them:

```bash
php artisan vendor:publish --tag=jaga-migrations
php artisan migrate
```

**3.** Add the `HasRoles` trait to your `User` model:

```php
use Laraditz\Jaga\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

**4.** Register the plugin in your Filament panel provider:

```php
use Laraditz\FilamentJaga\FilamentJagaPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(FilamentJagaPlugin::make());
}
```

**5.** Run the installer:

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

**6.** Protect your routes with the `jaga` middleware:

```php
// routes/web.php
Route::middleware(['auth', 'jaga'])->group(function () {
    Route::resource('posts', PostController::class);
});
```

**7.** Sync your named routes to the permissions table:

```bash
php artisan jaga:sync
```

After syncing, all your named routes will appear as permissions in the Filament panel, ready to be assigned to roles.

## ⚙️ Configuration

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

## 🎨 Customising the Plugin

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

## 🗄️ Cache & Sync

Jaga caches permission data for performance. After making changes to permissions or roles, manage the cache with:

```bash
php artisan jaga:sync    # discover and sync route permissions to the database
php artisan jaga:cache   # rebuild the permission cache
php artisan jaga:clear   # clear the permission cache
```

## 🌐 Publishing Translations

```bash
php artisan vendor:publish --tag=filament-jaga-lang
```

Language files will be published to `lang/vendor/filament-jaga`.

## 📦 Related

This plugin is a UI layer on top of [laraditz/jaga](https://github.com/laraditz/jaga). Head over there for the full documentation on permissions, roles, middleware usage, and more.

## License

MIT
