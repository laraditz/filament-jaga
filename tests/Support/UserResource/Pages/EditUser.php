<?php

namespace Laraditz\FilamentJaga\Tests\Support\UserResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Laraditz\FilamentJaga\Forms\Components\UserRolesField;
use Laraditz\FilamentJaga\Tests\Support\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            UserRolesField::make('jaga_roles'),
        ]);
    }
}
