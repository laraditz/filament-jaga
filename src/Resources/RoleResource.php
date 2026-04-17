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
     *
     * @param  array<int>    $permissionIds
     * @param  array<array>  $wildcards  e.g. [['pattern' => 'posts.*'], ...]
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
    public static function syncPermissionsByPatterns(array $patterns, callable $set): void
    {
        if (empty($patterns)) {
            return;
        }

        $hasGlobal = in_array('*', $patterns);
        $matches = fn(Permission $p) => $hasGlobal || collect($patterns)->contains(fn($pat) => fnmatch($pat, $p->name));

        // Route permissions by group
        Permission::where('is_custom', false)
            ->whereNotNull('group')->where('group', '!=', '')
            ->get()->groupBy('group')
            ->each(function ($perms, $group) use ($set, $matches) {
                $set('permissions_' . Str::slug($group, '_'), $perms->filter($matches)->pluck('id')->toArray());
            });

        // Ungrouped route permissions
        $ungrouped = Permission::where('is_custom', false)
            ->where(fn($q) => $q->whereNull('group')->orWhere('group', ''))
            ->get();
        if ($ungrouped->isNotEmpty()) {
            $set('permissions_ungrouped', $ungrouped->filter($matches)->pluck('id')->toArray());
        }

        // Custom permissions by group
        Permission::where('is_custom', true)
            ->whereNotNull('group')->where('group', '!=', '')
            ->get()->groupBy('group')
            ->each(function ($perms, $group) use ($set, $matches) {
                $set('permissions_custom_' . Str::slug($group, '_'), $perms->filter($matches)->pluck('id')->toArray());
            });

        // Ungrouped custom permissions
        $ungroupedCustom = Permission::where('is_custom', true)
            ->where(fn($q) => $q->whereNull('group')->orWhere('group', ''))
            ->get();
        if ($ungroupedCustom->isNotEmpty()) {
            $set('permissions_custom', $ungroupedCustom->filter($matches)->pluck('id')->toArray());
        }
    }

    /**
     * Clear all permission checkboxes, preserving the filament-jaga access permission
     * so the user cannot accidentally lock themselves out of the panel.
     */
    private static function clearPermissionsPreservingAccess(callable $set): void
    {
        $guard = Permission::where('name', config('filament-jaga.permission', 'jaga.access'))->first();

        Permission::where('is_custom', false)
            ->whereNotNull('group')->where('group', '!=', '')
            ->distinct()->pluck('group')
            ->each(fn($group) => $set('permissions_' . Str::slug($group, '_'), []));

        if (Permission::where('is_custom', false)->where(fn($q) => $q->whereNull('group')->orWhere('group', ''))->exists()) {
            $set('permissions_ungrouped', []);
        }

        Permission::where('is_custom', true)
            ->whereNotNull('group')->where('group', '!=', '')
            ->distinct()->pluck('group')
            ->each(function ($group) use ($set, $guard) {
                $slug = 'custom_' . Str::slug($group, '_');
                $keep = ($guard && $guard->is_custom && $guard->group === $group) ? [$guard->id] : [];
                $set("permissions_{$slug}", $keep);
            });

        if (Permission::where('is_custom', true)->where(fn($q) => $q->whereNull('group')->orWhere('group', ''))->exists()) {
            $keep = ($guard && $guard->is_custom && empty($guard->group)) ? [$guard->id] : [];
            $set('permissions_custom', $keep);
        }
    }

    public static function form(Schema $schema): Schema
    {
        // Route permission groups
        $routeGroups = Permission::where('is_custom', false)
            ->whereNotNull('group')->where('group', '!=', '')
            ->orderBy('group')->distinct()->pluck('group');

        $ungroupedRoutePermissions = Permission::where('is_custom', false)
            ->where(fn($q) => $q->whereNull('group')->orWhere('group', ''))
            ->orderBy('name')->get();

        // Custom permission groups
        $customGroups = Permission::where('is_custom', true)
            ->whereNotNull('group')->where('group', '!=', '')
            ->orderBy('group')->distinct()->pluck('group');

        $ungroupedCustomPermissions = Permission::where('is_custom', true)
            ->where(fn($q) => $q->whereNull('group')->orWhere('group', ''))
            ->orderBy('name')->get();

        // Primary label: description if set, otherwise name.
        // Secondary: always URI.
        $optionLabel = fn($p) => $p->description ?: $p->name;
        $optionUri = fn($p) => $p->uri ?: '';

        // Returns true if a specific permission (by ID) is currently locked by a wildcard.
        $coveredByWildcard = function (mixed $value, Get $get, Collection $collection): bool {
            $patterns = collect($get('wildcard_patterns') ?? [])->pluck('pattern')->filter()->toArray();
            if (empty($patterns)) {
                return false;
            }
            $hasGlobal = in_array('*', $patterns);
            $permission = $collection->firstWhere('id', $value);

            return $permission && ($hasGlobal || collect($patterns)->contains(fn($pat) => fnmatch($pat, $permission->name)));
        };

        // Returns true when ALL options in $collection are wildcard-locked (hides bulk-toggle).
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

        // Helper: build a CheckboxList for a given permission collection and field name.
        $makeCheckboxList = function (string $fieldName, Collection $perms) use ($optionLabel, $optionUri, $coveredByWildcard, $allCoveredByWildcard): Forms\Components\CheckboxList {
            $options = $perms->mapWithKeys(fn($p) => [$p->id => $optionLabel($p)])->toArray();

            return Forms\Components\CheckboxList::make($fieldName)
                ->hiddenLabel()
                ->options($options)
                ->descriptions($perms->mapWithKeys(fn($p) => [$p->id => $optionUri($p)])->toArray())
                ->in(array_keys($options))
                ->disableOptionWhen(fn(mixed $value, Get $get) => $coveredByWildcard($value, $get, $perms))
                ->bulkToggleable(fn(Get $get) => !$allCoveredByWildcard($get, $perms))
                ->columns(2);
        };

        // Helper: wrap a CheckboxList in a collapsible full-width Section.
        $makeSection = function (string $heading, Forms\Components\CheckboxList $list): Section {
            return Section::make($heading)->schema([$list])->collapsible()->columnSpanFull();
        };

        // --- Route Permissions tab ---
        $routeTabComponents = [];

        foreach ($routeGroups as $group) {
            $perms = Permission::where('group', $group)->where('is_custom', false)->orderBy('name')->get();
            $routeTabComponents[] = $makeSection($group, $makeCheckboxList('permissions_' . Str::slug($group, '_'), $perms));
        }

        if ($ungroupedRoutePermissions->isNotEmpty()) {
            $routeTabComponents[] = $makeSection(
                __('filament-jaga::filament-jaga.tabs.ungrouped'),
                $makeCheckboxList('permissions_ungrouped', $ungroupedRoutePermissions)
            );
        }

        // --- Custom Permissions tab ---
        $customTabComponents = [];

        foreach ($customGroups as $group) {
            $perms = Permission::where('group', $group)->where('is_custom', true)->orderBy('name')->get();
            $customTabComponents[] = $makeSection($group, $makeCheckboxList('permissions_custom_' . Str::slug($group, '_'), $perms));
        }

        if ($ungroupedCustomPermissions->isNotEmpty()) {
            $customTabComponents[] = $makeSection(
                __('filament-jaga::filament-jaga.tabs.ungrouped'),
                $makeCheckboxList('permissions_custom', $ungroupedCustomPermissions)
            );
        }

        return $schema->components([
            Section::make()->schema([
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

                Forms\Components\TextInput::make('guard_name')
                    ->label(__('filament-jaga::filament-jaga.fields.guard_name'))
                    ->required()
                    ->default('web')
                    ->maxLength(100),

                Forms\Components\Textarea::make('description')
                    ->label(__('filament-jaga::filament-jaga.fields.description'))
                    ->rows(2)
                    ->maxLength(500),
            ])
                ->columns(2)
                ->columnSpanFull(),
            Forms\Components\Toggle::make('select_all_permissions')
                ->label(__('filament-jaga::filament-jaga.fields.select_all_permissions'))
                ->live()
                ->dehydrated(false)
                ->columnSpanFull()
                ->afterStateUpdated(function (bool $state, callable $set) {
                    if ($state) {
                        $set('wildcard_patterns', [['pattern' => '*']]);
                        static::syncPermissionsByPatterns(['*'], $set);
                    } else {
                        $set('wildcard_patterns', []);
                        static::clearPermissionsPreservingAccess($set);
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
                                        ->afterStateUpdated(function (callable $set, Get $get) {
                                            $patterns = collect($get('../../wildcard_patterns') ?? [])
                                                ->pluck('pattern')->filter()->values()->toArray();
                                            static::syncPermissionsByPatterns($patterns, $set);
                                        }),
                                ])
                                ->live()
                                ->afterStateUpdated(function (?array $state, callable $set) {
                                    $patterns = collect($state ?? [])->pluck('pattern')->filter()->values()->toArray();
                                    static::syncPermissionsByPatterns($patterns, $set);
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
