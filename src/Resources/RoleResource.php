<?php

namespace Laraditz\FilamentJaga\Resources;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
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
     * Collect all permission IDs from grouped form fields (permissions_*) and remove them from $data.
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

    /**
     * Merge manually selected permissions with any additionally covered by wildcard patterns.
     * Wildcards are authoritative — permissions they cover are always included.
     *
     * @param  array<int>     $permissionIds
     * @param  array<array>   $wildcards  e.g. [['pattern' => 'posts.*'], ...]
     * @return array<int>
     */
    public static function resolvePermissionsWithWildcards(array $permissionIds, array $wildcards): array
    {
        $patterns = collect($wildcards)->pluck('pattern')->filter()->toArray();

        if (empty($patterns)) {
            return $permissionIds;
        }

        $hasGlobal = in_array('*', $patterns);

        $covered = Permission::all()
            ->filter(fn($p) => $hasGlobal || collect($patterns)->contains(fn($pat) => fnmatch($pat, $p->name)))
            ->pluck('id')
            ->toArray();

        return array_values(array_unique(array_merge($permissionIds, $covered)));
    }

    /**
     * Set permission checkbox states based on wildcard patterns.
     * When patterns is empty, the current selection is preserved — removing a wildcard
     * does not auto-uncheck permissions; the user manages them manually.
     *
     * @param  array<string>  $patterns
     */
    public static function syncPermissionsByPatterns(
        array $patterns,
        callable $set,
        Collection $groups,
        Collection $ungroupedPermissions,
        Collection $customPermissions,
    ): void {
        if (empty($patterns)) {
            return; // Preserve current checkbox state; no wildcard → no forced clearing
        }

        $hasGlobal = in_array('*', $patterns);
        $matches = fn(Permission $p) => $hasGlobal || collect($patterns)->contains(fn($pat) => fnmatch($pat, $p->name));

        foreach ($groups as $group) {
            $set(
                'permissions_' . Str::slug($group, '_'),
                Permission::where('group', $group)->where('is_custom', false)->orderBy('name')->get()->filter($matches)->pluck('id')->toArray()
            );
        }

        if ($ungroupedPermissions->isNotEmpty()) {
            $set('permissions_ungrouped', $ungroupedPermissions->filter($matches)->pluck('id')->toArray());
        }

        if ($customPermissions->isNotEmpty()) {
            $set('permissions_custom', $customPermissions->filter($matches)->pluck('id')->toArray());
        }
    }

    /**
     * Clear all permission checkboxes, preserving any permission whose name matches
     * the filament-jaga access gate (e.g. jaga.access) so the user cannot lock
     * themselves out of the panel.
     */
    private static function clearPermissionsPreservingAccess(
        callable $set,
        Collection $groups,
        Collection $ungroupedPermissions,
        Collection $customPermissions,
    ): void {
        $guardPermission = Permission::where('name', config('filament-jaga.permission', 'jaga.access'))->first();

        foreach ($groups as $group) {
            $slug = Str::slug($group, '_');
            $keep = ($guardPermission && !$guardPermission->is_custom && $guardPermission->group === $group)
                ? [$guardPermission->id] : [];
            $set("permissions_{$slug}", $keep);
        }

        if ($ungroupedPermissions->isNotEmpty()) {
            $keep = ($guardPermission && !$guardPermission->is_custom && empty($guardPermission->group))
                ? [$guardPermission->id] : [];
            $set('permissions_ungrouped', $keep);
        }

        if ($customPermissions->isNotEmpty()) {
            $keep = ($guardPermission && $guardPermission->is_custom)
                ? [$guardPermission->id] : [];
            $set('permissions_custom', $keep);
        }
    }

    public static function form(Schema $schema): Schema
    {
        $groups = Permission::where('is_custom', false)
            ->whereNotNull('group')
            ->where('group', '!=', '')
            ->orderBy('group')
            ->distinct()
            ->pluck('group');

        $ungroupedPermissions = Permission::where('is_custom', false)
            ->where(fn($q) => $q->whereNull('group')->orWhere('group', ''))
            ->orderBy('name')
            ->get();

        $customPermissions = Permission::where('is_custom', true)->orderBy('name')->get();
        $hasCustom = $customPermissions->isNotEmpty();

        // Returns true if a permission (by ID) is covered by the current wildcard_patterns form state.
        $coveredByWildcard = function (mixed $value, Get $get, Collection $collection): bool {
            $patterns = collect($get('wildcard_patterns') ?? [])->pluck('pattern')->filter()->toArray();

            if (empty($patterns)) {
                return false;
            }

            $hasGlobal = in_array('*', $patterns);
            $permission = $collection->firstWhere('id', $value);

            if (!$permission) {
                return false;
            }

            return $hasGlobal || collect($patterns)->contains(fn($pat) => fnmatch($pat, $permission->name));
        };

        // --- Route Permissions tab ---
        $routeTabComponents = [];

        // Returns true when ALL options in $collection are currently wildcard-locked.
        $allCoveredByWildcard = function (Get $get, Collection $collection): bool {
            $patterns = collect($get('wildcard_patterns') ?? [])->pluck('pattern')->filter()->toArray();
            if (empty($patterns)) {
                return false;
            }
            $hasGlobal = in_array('*', $patterns);

            return $collection->every(
                fn($p) => $hasGlobal || collect($patterns)->contains(fn($pat) => fnmatch($pat, $p->name))
            );
        };

        // Label helpers — description is primary, name is secondary.
        $optionLabel = fn($p) => $p->description ?: $p->name;
        $optionDescription = fn($p) => $p->description ? $p->name : ($p->uri ?: '');

        foreach ($groups as $group) {
            $slug = Str::slug($group, '_');
            $groupPermissions = Permission::where('group', $group)->where('is_custom', false)->orderBy('name')->get();
            $groupOptions = $groupPermissions->mapWithKeys(fn($p) => [$p->id => $optionLabel($p)])->toArray();

            $routeTabComponents[] = Section::make($group)
                ->schema([
                    Forms\Components\CheckboxList::make("permissions_{$slug}")
                        ->hiddenLabel()
                        ->options($groupOptions)
                        ->descriptions($groupPermissions->mapWithKeys(fn($p) => [$p->id => $optionDescription($p)])->toArray())
                        ->in(array_keys($groupOptions))
                        ->disableOptionWhen(fn(mixed $value, Get $get) => $coveredByWildcard($value, $get, $groupPermissions))
                        ->bulkToggleable(fn(Get $get) => !$allCoveredByWildcard($get, $groupPermissions))
                        ->columns(2),
                ])
                ->collapsible()
                ->columnSpanFull();
        }

        if ($ungroupedPermissions->isNotEmpty()) {
            $ungroupedOptions = $ungroupedPermissions->mapWithKeys(fn($p) => [$p->id => $optionLabel($p)])->toArray();

            $routeTabComponents[] = Section::make(__('filament-jaga::filament-jaga.tabs.ungrouped'))
                ->schema([
                    Forms\Components\CheckboxList::make('permissions_ungrouped')
                        ->hiddenLabel()
                        ->options($ungroupedOptions)
                        ->descriptions($ungroupedPermissions->mapWithKeys(fn($p) => [$p->id => $optionDescription($p)])->toArray())
                        ->in(array_keys($ungroupedOptions))
                        ->disableOptionWhen(fn(mixed $value, Get $get) => $coveredByWildcard($value, $get, $ungroupedPermissions))
                        ->bulkToggleable(fn(Get $get) => !$allCoveredByWildcard($get, $ungroupedPermissions))
                        ->columns(2),
                ])
                ->collapsible()
                ->columnSpanFull();
        }

        // --- Custom Permissions tab ---
        $customOptions = $customPermissions->mapWithKeys(fn($p) => [$p->id => $optionLabel($p)])->toArray();
        $customTabComponents = $hasCustom ? [
            Forms\Components\CheckboxList::make('permissions_custom')
                ->hiddenLabel()
                ->options($customOptions)
                ->descriptions($customPermissions->mapWithKeys(fn($p) => [$p->id => $optionDescription($p)])->toArray())
                ->in(array_keys($customOptions))
                ->disableOptionWhen(fn(mixed $value, Get $get) => $coveredByWildcard($value, $get, $customPermissions))
                ->bulkToggleable(fn(Get $get) => !$allCoveredByWildcard($get, $customPermissions))
                ->columns(2)
                ->columnSpanFull(),
        ] : [];

        return $schema->components([
            Section::make('Role Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('filament-jaga::filament-jaga.fields.name'))
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(
                            fn($state, callable $set) =>
                            $set('slug', Str::slug($state))
                        ),

                    Forms\Components\TextInput::make('slug')
                        ->label(__('filament-jaga::filament-jaga.fields.slug'))
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Textarea::make('description')
                        ->label(__('filament-jaga::filament-jaga.fields.description'))
                        ->rows(2)
                        ->maxLength(500),

                    Forms\Components\TextInput::make('guard_name')
                        ->label(__('filament-jaga::filament-jaga.fields.guard_name'))
                        ->required()
                        ->default('web')
                        ->maxLength(100),
                ])->columns(2)->columnSpanFull(),

            // Selecting all = wildcard '*'; visually reflects across all permission tabs
            Forms\Components\Toggle::make('select_all_permissions')
                ->label(__('filament-jaga::filament-jaga.fields.select_all_permissions'))
                ->live()
                ->dehydrated(false)
                ->columnSpanFull()
                ->afterStateUpdated(function (bool $state, callable $set) use ($groups, $ungroupedPermissions, $customPermissions) {
                    if ($state) {
                        $set('wildcard_patterns', [['pattern' => '*']]);
                        static::syncPermissionsByPatterns(['*'], $set, $groups, $ungroupedPermissions, $customPermissions);
                    } else {
                        $set('wildcard_patterns', []);
                        // Explicit clear when toggled OFF — preserve jaga.access so the user
                        // cannot accidentally lock themselves out of the panel.
                        static::clearPermissionsPreservingAccess($set, $groups, $ungroupedPermissions, $customPermissions);
                    }
                }),

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
                                        ->placeholder('e.g. reports.*')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (callable $set, Get $get) use ($groups, $ungroupedPermissions, $customPermissions) {
                                            $patterns = collect($get('../../wildcard_patterns') ?? [])
                                                ->pluck('pattern')
                                                ->filter()
                                                ->values()
                                                ->toArray();

                                            static::syncPermissionsByPatterns($patterns, $set, $groups, $ungroupedPermissions, $customPermissions);
                                        }),
                                ])
                                ->live()
                                ->afterStateUpdated(function (?array $state, callable $set) use ($groups, $ungroupedPermissions, $customPermissions) {
                                    $patterns = collect($state ?? [])->pluck('pattern')->filter()->values()->toArray();
                                    static::syncPermissionsByPatterns($patterns, $set, $groups, $ungroupedPermissions, $customPermissions);
                                })
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
