<?php

namespace Laraditz\FilamentJaga\Tests\Support;

use Filament\Resources\Resource;
use Laraditz\FilamentJaga\Tests\Models\User;
use Laraditz\FilamentJaga\Tests\Support\UserResource\Pages\EditUser;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function getPages(): array
    {
        return [
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return '/';
    }
}
