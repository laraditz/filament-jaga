<?php

namespace Laraditz\FilamentJaga\Resources;

use Filament\Resources\Resource;
use Laraditz\Jaga\Models\Permission;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;
}
