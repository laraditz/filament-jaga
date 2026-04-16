<?php

namespace Laraditz\FilamentJaga;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Laraditz\FilamentJaga\Pages\JagaDashboard;
use Laraditz\FilamentJaga\Resources\PermissionResource;
use Laraditz\FilamentJaga\Resources\RoleResource;

class FilamentJagaPlugin implements Plugin
{
    protected string $navigationGroup;
    protected string $navigationIcon;
    protected int $navigationSort;
    protected string $permission;
    protected string $userModel;
    protected string $dashboardSlug;
    protected array $resources;

    public function __construct()
    {
        $this->navigationGroup = config('filament-jaga.navigation.group', 'Roles & Permissions');
        $this->navigationIcon  = config('filament-jaga.navigation.icon', 'heroicon-o-shield-check');
        $this->navigationSort  = config('filament-jaga.navigation.sort', 10);
        $this->permission      = config('filament-jaga.permission', 'jaga.access');
        $this->userModel       = config('filament-jaga.user_model', \App\Models\User::class);
        $this->dashboardSlug   = config('filament-jaga.dashboard_slug', 'jaga');
        $this->resources       = config('filament-jaga.resources', ['roles' => true, 'permissions' => true]);
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-jaga';
    }

    public function register(Panel $panel): void
    {
        $resources = [];

        if ($this->isResourceEnabled('roles')) {
            $resources[] = RoleResource::class;
        }

        if ($this->isResourceEnabled('permissions')) {
            $resources[] = PermissionResource::class;
        }

        $panel->resources($resources);
        $panel->pages([JagaDashboard::class]);
    }

    public function boot(Panel $panel): void
    {
        // Reserved for future use
    }

    // --- Fluent setters ---

    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;
        return $this;
    }

    public function navigationIcon(string $icon): static
    {
        $this->navigationIcon = $icon;
        return $this;
    }

    public function navigationSort(int $sort): static
    {
        $this->navigationSort = $sort;
        return $this;
    }

    public function permission(string $permission): static
    {
        $this->permission = $permission;
        return $this;
    }

    public function userModel(string $userModel): static
    {
        $this->userModel = $userModel;
        return $this;
    }

    public function dashboardSlug(string $slug): static
    {
        $this->dashboardSlug = $slug;
        return $this;
    }

    public function disableResource(string $name): static
    {
        $this->resources[$name] = false;
        return $this;
    }

    public function enableResource(string $name): static
    {
        $this->resources[$name] = true;
        return $this;
    }

    // --- Getters ---

    public function getNavigationGroup(): string    { return $this->navigationGroup; }
    public function getNavigationIcon(): string     { return $this->navigationIcon; }
    public function getNavigationSort(): int        { return $this->navigationSort; }
    public function getPermission(): string         { return $this->permission; }
    public function getUserModel(): string          { return $this->userModel; }
    public function getDashboardSlug(): string      { return $this->dashboardSlug; }

    public function isResourceEnabled(string $name): bool
    {
        return $this->resources[$name] ?? true;
    }
}
