<?php

namespace Laraditz\FilamentJaga\Resources\PermissionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Laraditz\FilamentJaga\Resources\PermissionResource;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('filament-jaga::filament-jaga.tabs.all')),
            'route' => Tab::make(__('filament-jaga::filament-jaga.tabs.route'))
                ->modifyQueryUsing(fn ($query) => $query->where('is_custom', false)),
            'custom' => Tab::make(__('filament-jaga::filament-jaga.tabs.custom'))
                ->modifyQueryUsing(fn ($query) => $query->where('is_custom', true)),
        ];
    }
}
