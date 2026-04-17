<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <x-filament::card>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('filament-jaga::filament-jaga.widgets.stats.total_roles') }}
            </div>
            <div class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
                {{ \Laraditz\Jaga\Models\Role::count() }}
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('filament-jaga::filament-jaga.widgets.stats.total_permissions') }}
            </div>
            <div class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
                {{ \Laraditz\Jaga\Models\Permission::count() }}
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('filament-jaga::filament-jaga.widgets.stats.users_with_roles') }}
            </div>
            <div class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
                {{ \Illuminate\Support\Facades\DB::table(config('jaga.tables.model_role', 'model_role'))->distinct('model_id')->count() }}
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
