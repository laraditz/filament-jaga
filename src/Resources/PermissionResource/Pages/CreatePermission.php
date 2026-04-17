<?php

namespace Laraditz\FilamentJaga\Resources\PermissionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Laraditz\FilamentJaga\Resources\PermissionResource;

class CreatePermission extends CreateRecord
{
    protected static string $resource = PermissionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['is_custom']           = true;
        $data['is_auto_description'] = false;
        $data['methods']             = $data['methods'] ?? [];
        $data['uri']                 = $data['uri'] ?? '';

        return static::getModel()::create($data);
    }
}
