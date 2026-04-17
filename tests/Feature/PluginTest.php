<?php

use Laraditz\FilamentJaga\FilamentJagaPlugin;

it('has the correct id', function () {
    expect(FilamentJagaPlugin::make()->getId())->toBe('filament-jaga');
});

it('returns the default permission', function () {
    expect(FilamentJagaPlugin::make()->getPermission())->toBe('jaga.access');
});

it('supports fluent permission override', function () {
    $plugin = FilamentJagaPlugin::make()->permission('admin.jaga');
    expect($plugin->getPermission())->toBe('admin.jaga');
});

it('supports fluent navigation group override', function () {
    $plugin = FilamentJagaPlugin::make()->navigationGroup('Admin');
    expect($plugin->getNavigationGroup())->toBe('Admin');
});

it('supports disableResource and enableResource', function () {
    $plugin = FilamentJagaPlugin::make()->disableResource('permissions');
    expect($plugin->isResourceEnabled('permissions'))->toBeFalse();
    expect($plugin->isResourceEnabled('roles'))->toBeTrue();

    $plugin->enableResource('permissions');
    expect($plugin->isResourceEnabled('permissions'))->toBeTrue();
});

it('supports all fluent config methods', function () {
    $plugin = FilamentJagaPlugin::make()
        ->navigationIcon('heroicon-o-lock-closed')
        ->navigationSort(5)
        ->userModel(\App\Models\User::class)
        ->dashboardSlug('rbac');

    expect($plugin->getNavigationIcon())->toBe('heroicon-o-lock-closed');
    expect($plugin->getNavigationSort())->toBe(5);
    expect($plugin->getUserModel())->toBe(\App\Models\User::class);
    expect($plugin->getDashboardSlug())->toBe('rbac');
});
