<?php

namespace Laraditz\FilamentJaga\Tests\Support;

use Filament\Panel;
use Filament\PanelProvider;
use Laraditz\FilamentJaga\FilamentJagaPlugin;
use Laraditz\FilamentJaga\Tests\Support\UserResource;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('test')
            ->path('test')
            ->resources([UserResource::class])
            ->plugin(FilamentJagaPlugin::make());
    }
}
