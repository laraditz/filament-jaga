<?php

namespace Laraditz\FilamentJaga\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Laraditz\FilamentJaga\Resources\PermissionResource\Pages;
use Laraditz\Jaga\Enums\AccessLevel;
use Laraditz\Jaga\Models\Permission;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    public static function getNavigationGroup(): ?string
    {
        return app('filament')->getPlugin('filament-jaga')->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return app('filament')->getPlugin('filament-jaga')->getNavigationSort() + 1;
    }

    public static function getLabel(): string
    {
        return __('filament-jaga::filament-jaga.resources.permissions.label');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-jaga::filament-jaga.resources.permissions.plural_label');
    }

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->hasPermission(config('filament-jaga.permission'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-jaga::filament-jaga.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('group')
                    ->label(__('filament-jaga::filament-jaga.fields.group'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('methods')
                    ->label(__('filament-jaga::filament-jaga.fields.methods'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => implode(', ', (array) $state)),
                Tables\Columns\TextColumn::make('uri')
                    ->label('URI')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('filament-jaga::filament-jaga.fields.description'))
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('access_level')
                    ->label(__('filament-jaga::filament-jaga.fields.access_level'))
                    ->badge()
                    ->color(fn (AccessLevel $state): string => match ($state) {
                        AccessLevel::Public     => 'success',
                        AccessLevel::Auth       => 'warning',
                        AccessLevel::Restricted => 'danger',
                    }),
                Tables\Columns\IconColumn::make('is_custom')
                    ->label(__('filament-jaga::filament-jaga.fields.is_custom'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-jaga::filament-jaga.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('group')
            ->filters([
                Tables\Filters\SelectFilter::make('access_level')
                    ->label(__('filament-jaga::filament-jaga.fields.access_level'))
                    ->options(AccessLevel::class),
                Tables\Filters\TernaryFilter::make('is_custom')
                    ->label(__('filament-jaga::filament-jaga.fields.is_custom')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
        ];
    }
}
