<?php

namespace Laraditz\FilamentJaga\Tests;

use Filament\FilamentServiceProvider;
use Filament\Panel;
use Filament\Support\SupportServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laraditz\FilamentJaga\FilamentJagaPlugin;
use Laraditz\FilamentJaga\FilamentJagaServiceProvider;
use Laraditz\Jaga\JagaServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app('filament')->registerPanel(
            Panel::make()
                ->default()
                ->id('test')
                ->path('test')
                ->plugin(FilamentJagaPlugin::make())
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            FilamentServiceProvider::class,
            JagaServiceProvider::class,
            FilamentJagaServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(
            __DIR__ . '/../vendor/laraditz/jaga/database/migrations'
        );

        // Orchestra Testbench v10 ships a users migration
        $this->loadMigrationsFrom(
            __DIR__ . '/../vendor/orchestra/testbench-core/laravel/migrations'
        );
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Use the test User model that has HasRoles
        $app['config']->set('filament-jaga.user_model', \Laraditz\FilamentJaga\Tests\Models\User::class);
        $app['config']->set('auth.providers.users.model', \Laraditz\FilamentJaga\Tests\Models\User::class);
    }
}
