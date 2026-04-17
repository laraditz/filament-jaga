<?php

it('merges the filament-jaga config', function () {
    expect(config('filament-jaga.permission'))->toBe('jaga.access');
    expect(config('filament-jaga.navigation.group'))->toBe('Roles & Permissions');
});

it('registers the jaga:install command', function () {
    expect(
        collect(\Illuminate\Support\Facades\Artisan::all())
            ->keys()
            ->contains('jaga:install')
    )->toBeTrue();
});
