<?php

namespace Laraditz\FilamentJaga\Resources\RoleResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laraditz\FilamentJaga\Resources\RoleResource;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $wildcards = DB::table(config('jaga.tables.role_permission'))
            ->where('role_id', $this->record->id)
            ->whereNull('permission_id')
            ->pluck('wildcard')
            ->map(fn ($w) => ['pattern' => $w])
            ->values()
            ->toArray();

        $data['wildcard_patterns'] = $wildcards;

        // Reflect '*' wildcard in the Select All toggle
        $data['select_all_permissions'] = collect($wildcards)->pluck('pattern')->contains('*');

        // Split permissions into per-group fields to match the tab structure
        $permissions = $this->record->permissions()->get();

        $permissions->where('is_custom', false)
            ->filter(fn ($p) => ! empty($p->group))
            ->groupBy('group')
            ->each(function ($groupPerms, $group) use (&$data) {
                $data['permissions_' . Str::slug($group, '_')] = $groupPerms->pluck('id')->toArray();
            });

        $ungroupedIds = $permissions->where('is_custom', false)
            ->filter(fn ($p) => empty($p->group))
            ->pluck('id')
            ->toArray();

        if (! empty($ungroupedIds)) {
            $data['permissions_ungrouped'] = $ungroupedIds;
        }

        $customPerms = $permissions->where('is_custom', true);

        $customPerms->filter(fn ($p) => ! empty($p->group))
            ->groupBy('group')
            ->each(function ($groupPerms, $group) use (&$data) {
                $data['permissions_custom_' . Str::slug($group, '_')] = $groupPerms->pluck('id')->toArray();
            });

        $ungroupedCustomIds = $customPerms->filter(fn ($p) => empty($p->group))->pluck('id')->toArray();

        if (! empty($ungroupedCustomIds)) {
            $data['permissions_custom'] = $ungroupedCustomIds;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $wildcards     = $data['wildcard_patterns'] ?? [];
        $permissionIds = RoleResource::collectPermissionIds($data);
        $permissionIds = RoleResource::resolvePermissionsWithWildcards($permissionIds, $wildcards);
        unset($data['wildcard_patterns'], $data['select_all_permissions']);

        $record->update($data);

        $record->permissions()->sync($permissionIds);

        $table = config('jaga.tables.role_permission');
        DB::table($table)->where('role_id', $record->id)->whereNull('permission_id')->delete();

        foreach ($wildcards as $entry) {
            if (! empty($entry['pattern'])) {
                DB::table($table)->insert([
                    'role_id'       => $record->id,
                    'permission_id' => null,
                    'wildcard'      => $entry['pattern'],
                    'created_at'    => now(),
                ]);
            }
        }

        return $record;
    }
}
