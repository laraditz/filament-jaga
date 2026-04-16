<?php

namespace Laraditz\FilamentJaga\Resources;

use Filament\Resources\Resource;
use Laraditz\Jaga\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
}
