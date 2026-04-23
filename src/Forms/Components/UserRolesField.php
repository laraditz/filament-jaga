<?php

namespace Laraditz\FilamentJaga\Forms\Components;

use Filament\Forms\Components\CheckboxList;
use Laraditz\Jaga\Models\Role;

class UserRolesField extends CheckboxList
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->options(fn () => Role::orderBy('name')->pluck('name', 'id')->toArray());

        $this->descriptions(fn () => Role::orderBy('name')->pluck('slug', 'id')->toArray());

        $this->bulkToggleable();

        $this->columns(2);

        $this->dehydrated(false);

        $this->afterStateHydrated(function (self $component, $record): void {
            $component->state($record ? $record->roles->pluck('id')->toArray() : []);
        });

        $this->saveRelationshipsUsing(function ($record, array $state): void {
            $currentRoleIds = $record->roles->pluck('id')->toArray();
            $newRoleIds = array_map('intval', $state);

            $toAssign = array_diff($newRoleIds, $currentRoleIds);
            $toRemove = array_diff($currentRoleIds, $newRoleIds);

            if ($toAssign) {
                $record->assignRole($toAssign);
            }

            if ($toRemove) {
                $record->removeRole($toRemove);
            }
        });
    }
}
