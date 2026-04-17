# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-04-17

### Added

#### Plugin
- `FilamentJagaPlugin` with fluent API: `navigationGroup()`, `navigationIcon()`, `navigationSort()`, `permission()`, `userModel()`, `disableResource()`
- Automatic registration of Roles and Permissions resources under a configurable sidebar group
- `jaga:install` Artisan command — publishes config, seeds `jaga.access` permission and `super-admin` role, and assigns the role to a chosen user

#### Roles Resource
- Full CRUD for roles (create, edit, delete)
- Auto-generated slug from role name on create
- Permission assignment grouped by permission group with checkbox lists
- Wildcard pattern support (e.g. `posts.*`) on the edit page via a dedicated repeater
- `select all` per group and global select-all shortcuts
- Navigation sorted relative to Permissions resource

#### Permissions Resource
- All / Route / Custom tabs for quick filtering
- Table rows grouped by permission group with collapsible groups
- Filter by access level (`public`, `auth`, `restricted`)
- Soft-delete filter (TrashedFilter)
- Edit group, description, and access level on any permission
- Create custom permissions (auto-sets `is_custom = true`)
- Delete restricted to custom permissions; auto-discovered route permissions cannot be deleted
- Bulk delete action
- **Roles relation manager** — view, attach, and detach roles assigned to a permission
- **Users relation manager** — view, attach, and detach users assigned directly to a permission

#### Configuration
- `config/filament-jaga.php` with navigation, resource visibility, permission, and user model options
- Publishable translations (`filament-jaga-lang`) with full English strings for all UI labels, tabs, fields, and relation managers
