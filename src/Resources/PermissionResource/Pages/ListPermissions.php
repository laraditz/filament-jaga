<?php

namespace Laraditz\FilamentJaga\Resources\PermissionResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Laraditz\FilamentJaga\Resources\PermissionResource;
use Laraditz\Jaga\Jobs\SyncPermissionsJob;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('sync')
                ->label(__('filament-jaga::filament-jaga.sync_permissions.label'))
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading(__('filament-jaga::filament-jaga.sync_permissions.modal_heading'))
                ->modalDescription(__('filament-jaga::filament-jaga.sync_permissions.modal_description'))
                ->action(function () {
                    SyncPermissionsJob::dispatch();

                    Notification::make()
                        ->title(__('filament-jaga::filament-jaga.sync_permissions.success_notification'))
                        ->success()
                        ->send();
                }),
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
