<?php

namespace Laraditz\FilamentJaga;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentJagaPlugin implements Plugin
{
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
        // Full implementation in Task 5
    }

    public function boot(Panel $panel): void
    {
        // Reserved for future use
    }
}
