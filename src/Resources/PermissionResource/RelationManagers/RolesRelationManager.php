<?php

namespace Laraditz\FilamentJaga\Resources\PermissionResource\RelationManagers;

use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('filament-jaga::filament-jaga.tabs.roles');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-jaga::filament-jaga.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('filament-jaga::filament-jaga.fields.slug'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-jaga::filament-jaga.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Actions\AttachAction::make()
                    ->label(__('filament-jaga::filament-jaga.relation_managers.roles.attach_label'))
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Actions\DetachAction::make()
                    ->label(__('filament-jaga::filament-jaga.relation_managers.roles.detach_label')),
            ])
            ->bulkActions([
                Actions\DetachBulkAction::make(),
            ]);
    }
}
