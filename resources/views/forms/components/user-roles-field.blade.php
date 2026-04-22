<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @php
        $roles        = $getRoles();
        $groupedPerms = $getGroupedPermissions();
        $statePath    = $getStatePath();
    @endphp

    {{-- Roles section --}}
    <div class="space-y-2">
        <div class="text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ __('filament-jaga::filament-jaga.user_roles_field.roles_label') }}
        </div>

        <div class="grid grid-cols-1 gap-1 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($roles as $role)
                <label class="flex items-start gap-2 text-sm">
                    <input
                        type="checkbox"
                        value="{{ $role->id }}"
                        {{ $applyStateBindingModifiers("wire:model={$statePath}.roles") }}
                        class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    >
                    <span>
                        {{ $role->name }}
                        @if ($role->slug)
                            <span class="block text-xs text-gray-400">{{ $role->slug }}</span>
                        @endif
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Permissions section --}}
    <div class="mt-4 space-y-4">
        <div class="text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ __('filament-jaga::filament-jaga.user_roles_field.permissions_label') }}
        </div>

        @foreach ($groupedPerms as $group => $permissions)
            <div>
                <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ $group }}
                </div>

                <div class="grid grid-cols-1 gap-1 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($permissions as $permission)
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                value="{{ $permission->id }}"
                                {{ $applyStateBindingModifiers("wire:model={$statePath}.permissions") }}
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            >
                            {{ $permission->name }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-dynamic-component>
