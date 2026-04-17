<?php

namespace Laraditz\FilamentJaga\Resources\PermissionResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $inverseRelationship = null;

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('filament-jaga::filament-jaga.tabs.users');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    protected function getTableQuery(): Builder
    {
        $userModel = config('filament-jaga.user_model');

        return $userModel::query()->whereIn(
            (new $userModel)->getKeyName(),
            DB::table(config('jaga.tables.model_permission'))
                ->where('model_type', $userModel)
                ->where('permission_id', $this->ownerRecord->id)
                ->whereNotNull('permission_id')
                ->pluck('model_id')
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-jaga::filament-jaga.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-jaga::filament-jaga.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Actions\Action::make('attach')
                    ->label(__('filament-jaga::filament-jaga.relation_managers.users.attach_label'))
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label(__('filament-jaga::filament-jaga.fields.name'))
                            ->options(fn () => config('filament-jaga.user_model')::query()->pluck('email', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        DB::table(config('jaga.tables.model_permission'))->insert([
                            'model_type'    => config('filament-jaga.user_model'),
                            'model_id'      => $data['user_id'],
                            'permission_id' => $this->ownerRecord->id,
                            'created_at'    => now(),
                        ]);
                    }),
            ])
            ->actions([
                Actions\Action::make('detach')
                    ->label(__('filament-jaga::filament-jaga.relation_managers.users.detach_label'))
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        DB::table(config('jaga.tables.model_permission'))
                            ->where('model_type', config('filament-jaga.user_model'))
                            ->where('model_id', $record->id)
                            ->where('permission_id', $this->ownerRecord->id)
                            ->delete();
                    }),
            ]);
    }
}
