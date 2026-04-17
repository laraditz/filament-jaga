<?php

namespace Laraditz\FilamentJaga\Resources;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Laraditz\FilamentJaga\Resources\RoleResource\Pages;
use Laraditz\Jaga\Models\Permission;
use Laraditz\Jaga\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-user-group';

    public static function getNavigationGroup(): ?string
    {
        return app('filament')->getPlugin('filament-jaga')->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return app('filament')->getPlugin('filament-jaga')->getNavigationSort();
    }

    public static function getLabel(): string
    {
        return __('filament-jaga::filament-jaga.resources.roles.label');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-jaga::filament-jaga.resources.roles.plural_label');
    }

    public static function canAccess(): bool
    {
        return auth()->check()
            && auth()->user()->hasPermission(config('filament-jaga.permission'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label(__('filament-jaga::filament-jaga.fields.name'))
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) =>
                    $set('slug', Str::slug($state))
                ),

            Forms\Components\TextInput::make('slug')
                ->label(__('filament-jaga::filament-jaga.fields.slug'))
                ->required()
                ->maxLength(255)
                ->disabled(fn (string $context) => $context === 'edit'),

            Forms\Components\CheckboxList::make('permissions')
                ->label(__('filament-jaga::filament-jaga.fields.permissions'))
                ->relationship('permissions', 'name')
                ->options(function () {
                    return Permission::orderBy('group')->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn ($p) => [$p->id => $p->description ?: $p->name])
                        ->toArray();
                })
                ->columns(2),

            Forms\Components\Repeater::make('wildcard_patterns')
                ->label(__('filament-jaga::filament-jaga.fields.wildcard_patterns'))
                ->schema([
                    Forms\Components\TextInput::make('pattern')
                        ->required()
                        ->placeholder('e.g. reports.*'),
                ])
                ->addActionLabel('Add Wildcard Pattern')
                ->defaultItems(0),
        ]);
    }

    public static function table(Table $table): Table
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
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label(__('filament-jaga::filament-jaga.fields.permissions_count'))
                    ->counts('permissions'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-jaga::filament-jaga.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit'   => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
