<?php

namespace Laraditz\FilamentJaga\Resources\RoleResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $wildcards = $data['wildcard_patterns'] ?? [];
        unset($data['wildcard_patterns']);

        $record->update($data);

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
