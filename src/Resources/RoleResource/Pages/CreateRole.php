<?php

namespace Laraditz\FilamentJaga\Resources\RoleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laraditz\FilamentJaga\Resources\RoleResource;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug']       = $data['slug'] ?? Str::slug($data['name']);
        $data['guard_name'] = config('jaga.guard', 'web');
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $wildcards   = $data['wildcard_patterns'] ?? [];
        $permissions = $data['permissions'] ?? [];
        unset($data['wildcard_patterns'], $data['permissions']);

        $role = static::getModel()::create($data);

        if (! empty($permissions)) {
            $role->permissions()->sync($permissions);
        }

        $this->syncWildcards($role, $wildcards);

        return $role;
    }

    private function syncWildcards(Model $role, array $wildcards): void
    {
        $table = config('jaga.tables.role_permission');

        DB::table($table)
            ->where('role_id', $role->id)
            ->whereNull('permission_id')
            ->delete();

        foreach ($wildcards as $entry) {
            if (! empty($entry['pattern'])) {
                DB::table($table)->insert([
                    'role_id'       => $role->id,
                    'permission_id' => null,
                    'wildcard'      => $entry['pattern'],
                    'created_at'    => now(),
                ]);
            }
        }
    }
}
