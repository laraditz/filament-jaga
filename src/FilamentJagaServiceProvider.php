<?php

namespace Laraditz\FilamentJaga;

use Illuminate\Support\ServiceProvider;
use Laraditz\FilamentJaga\Commands\InstallCommand;

class FilamentJagaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/filament-jaga.php',
            'filament-jaga'
        );
    }

    public function boot(): void
    {
        $this->commands([
            InstallCommand::class,
        ]);

        $this->loadTranslationsFrom(
            __DIR__ . '/../resources/lang',
            'filament-jaga'
        );

        $this->loadViewsFrom(
            __DIR__ . '/../resources/views',
            'filament-jaga'
        );

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/filament-jaga.php' => config_path('filament-jaga.php'),
            ], 'filament-jaga-config');

            $this->publishes([
                __DIR__ . '/../resources/lang' => lang_path('vendor/filament-jaga'),
            ], 'filament-jaga-lang');
        }
    }
}
