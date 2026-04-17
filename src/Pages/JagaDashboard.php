<?php

namespace Laraditz\FilamentJaga\Pages;

use Filament\Pages\Page;

class JagaDashboard extends Page
{
    protected string $view = 'filament-jaga::pages.dashboard';

    public static function getNavigationLabel(): string
    {
        return __('filament-jaga::filament-jaga.pages.dashboard.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return app('filament')->getPlugin('filament-jaga')->getNavigationGroup();
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chart-bar-square';
    }

    public static function getNavigationSort(): ?int
    {
        return app('filament')->getPlugin('filament-jaga')->getNavigationSort() - 1;
    }

    public static function getSlug(?\Filament\Panel $panel = null): string
    {
        return config('filament-jaga.dashboard_slug', 'jaga');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('filament-jaga::filament-jaga.pages.dashboard.title');
    }

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->hasPermission(config('filament-jaga.permission'));
    }
}
