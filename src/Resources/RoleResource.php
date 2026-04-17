<?php

namespace Laraditz\FilamentJaga\Resources;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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

    /**
     * Collect all permission IDs from the grouped form fields (permissions_*).
     *
     * @param  array<string, mixed>  $data
     * @return array<int>
     */
    public static function collectPermissionIds(array &$data): array
    {
        $ids = [];

        foreach (array_keys($data) as $key) {
            if (str_starts_with($key, 'permissions_') && is_array($data[$key])) {
                $ids = array_merge($ids, $data[$key]);
                unset($data[$key]);
            }
        }

        return array_values(array_filter($ids));
    }

    public static function form(Schema $schema): Schema
    {
        $groups = Permission::where('is_custom', false)
            ->whereNotNull('group')
            ->orderBy('group')
            ->distinct()
            ->pluck('group');

        $customPermissions = Permission::where('is_custom', true)->orderBy('name')->get();
        $hasCustom         = $customPermissions->isNotEmpty();

        // --- Route Permissions tab ---
        $routeTabComponents = [];

        if ($groups->isNotEmpty()) {
            $routeTabComponents[] = Forms\Components\Toggle::make('select_all_permissions')
                ->label(__('filament-jaga::filament-jaga.fields.select_all_permissions'))
                ->live()
                ->dehydrated(false)
                ->columnSpanFull()
                ->afterStateUpdated(function (bool $state, callable $set) use ($groups) {
                    foreach ($groups as $group) {
                        $slug = Str::slug($group, '_');
                        $set("permissions_{$slug}", $state
                            ? Permission::where('group', $group)->where('is_custom', false)->pluck('id')->toArray()
                            : []
                        );
                    }
                });

            foreach ($groups as $group) {
                $slug             = Str::slug($group, '_');
                $groupPermissions = Permission::where('group', $group)
                    ->where('is_custom', false)
                    ->orderBy('name')
                    ->get();

                $options      = $groupPermissions->mapWithKeys(fn ($p) => [$p->id => $p->name])->toArray();
                $descriptions = $groupPermissions->mapWithKeys(fn ($p) => [$p->id => $p->uri ?: ''])->toArray();

                $routeTabComponents[] = Section::make($group)
                    ->schema([
                        Forms\Components\CheckboxList::make("permissions_{$slug}")
                            ->hiddenLabel()
                            ->options($options)
                            ->descriptions($descriptions)
                            ->columns(2)
                            ->bulkToggleable(),
                    ])
                    ->collapsible()
                    ->columnSpanFull();
            }
        }

        // --- Custom Permissions tab ---
        $customTabComponents = [];

        if ($hasCustom) {
            $customOptions      = $customPermissions->mapWithKeys(fn ($p) => [$p->id => $p->name])->toArray();
            $customDescriptions = $customPermissions->mapWithKeys(fn ($p) => [$p->id => $p->uri ?: ''])->toArray();

            $customTabComponents[] = Forms\Components\CheckboxList::make('permissions_custom')
                ->hiddenLabel()
                ->options($customOptions)
                ->descriptions($customDescriptions)
                ->columns(2)
                ->bulkToggleable()
                ->columnSpanFull();
        }

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

            Tabs::make('permissions_tabs')
                ->tabs([
                    Tab::make(__('filament-jaga::filament-jaga.tabs.route_permissions'))
                        ->icon('heroicon-o-globe-alt')
                        ->schema($routeTabComponents),

                    Tab::make(__('filament-jaga::filament-jaga.tabs.custom_permissions'))
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->schema($customTabComponents),

                    Tab::make(__('filament-jaga::filament-jaga.tabs.wildcard_patterns'))
                        ->icon('heroicon-o-variable')
                        ->schema([
                            Forms\Components\Placeholder::make('wildcard_hint')
                                ->hiddenLabel()
                                ->content(__('filament-jaga::filament-jaga.tabs.wildcard_hint')),

                            Forms\Components\Repeater::make('wildcard_patterns')
                                ->hiddenLabel()
                                ->schema([
                                    Forms\Components\TextInput::make('pattern')
                                        ->required()
                                        ->placeholder('e.g. reports.*'),
                                ])
                                ->addActionLabel(__('filament-jaga::filament-jaga.actions.add_wildcard'))
                                ->defaultItems(0)
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),
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
