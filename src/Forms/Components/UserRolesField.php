<?php

namespace Laraditz\FilamentJaga\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Laraditz\Jaga\Models\Permission;
use Laraditz\Jaga\Models\Role;

class UserRolesField extends Field
{
    protected string $view = 'filament-jaga::forms.components.user-roles-field';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);

        $this->afterStateHydrated(function (self $component, $record): void {
            if (! $record) {
                $component->state(['roles' => [], 'permissions' => []]);

                return;
            }

            $component->state([
                'roles'       => $record->roles->pluck('id')->toArray(),
                'permissions' => $record->permissions()->pluck('id')->toArray(),
            ]);
        });

        $this->saveRelationshipsUsing(function ($record, $state): void {
            $currentRoleIds = $record->roles->pluck('id')->toArray();
            $freshPermIds   = $record->permissions()->pluck('id')->toArray();

            $newRoleIds = array_map('intval', $state['roles'] ?? []);
            $newPermIds = array_map('intval', $state['permissions'] ?? []);

            $toAssign = array_diff($newRoleIds, $currentRoleIds);
            $toRemove = array_diff($currentRoleIds, $newRoleIds);

            if ($toAssign) {
                $record->assignRole($toAssign);
            }

            if ($toRemove) {
                $record->removeRole($toRemove);
            }

            $toGrant  = array_diff($newPermIds, $freshPermIds);
            $toRevoke = array_diff($freshPermIds, $newPermIds);

            foreach ($toGrant as $id) {
                $record->grantPermission($id);
            }

            foreach ($toRevoke as $id) {
                $record->revokePermission($id);
            }
        });
    }

    public function getRoles(): Collection
    {
        return Role::orderBy('name')->get();
    }

    public function getGroupedPermissions(): SupportCollection
    {
        return Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
    }
}
