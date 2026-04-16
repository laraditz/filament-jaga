<?php

namespace Laraditz\FilamentJaga\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laraditz\Jaga\Models\Permission;
use Laraditz\Jaga\Models\Role;

class InstallCommand extends Command
{
    protected $signature = 'jaga:install
                            {--assign : Only assign the super-admin role to a user (skip seeding)}
                            {--email= : Email of the user to assign the super-admin role to}
                            {--force : Re-publish config even if it already exists}';

    protected $description = 'Install and set up filament-jaga (seeds permission, role, and optionally assigns a user)';

    public function handle(): int
    {
        if (! $this->option('assign')) {
            $this->publishConfig();
            $this->seedPermission();
            $this->seedRole();
            $this->assignPermissionsToRole();
        }

        return $this->assignUser();
    }

    private function publishConfig(): void
    {
        $this->callSilently('vendor:publish', [
            '--tag'   => 'filament-jaga-config',
            '--force' => $this->option('force'),
        ]);
    }

    private function seedPermission(): void
    {
        Permission::firstOrCreate(
            ['name' => 'jaga.access'],
            [
                'methods'             => ['GET'],
                'uri'                 => 'jaga',
                'description'         => 'Access the Filament Jaga management panel',
                'is_auto_description' => false,
                'is_custom'           => true,
                'access_level'        => 'restricted',
                'group'               => 'Jaga',
            ]
        );
    }

    private function seedRole(): void
    {
        Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'guard_name' => config('jaga.guard', 'web')]
        );
    }

    private function assignPermissionsToRole(): void
    {
        $role       = Role::where('slug', 'super-admin')->firstOrFail();
        $permission = Permission::where('name', 'jaga.access')->firstOrFail();
        $table      = config('jaga.tables.role_permission');

        if (! DB::table($table)->where('role_id', $role->id)->where('permission_id', $permission->id)->exists()) {
            DB::table($table)->insert([
                'role_id'       => $role->id,
                'permission_id' => $permission->id,
                'wildcard'      => null,
                'created_at'    => now(),
            ]);
        }

        if (! DB::table($table)->where('role_id', $role->id)->where('wildcard', '*')->exists()) {
            DB::table($table)->insert([
                'role_id'       => $role->id,
                'permission_id' => null,
                'wildcard'      => '*',
                'created_at'    => now(),
            ]);
        }
    }

    private function assignUser(): int
    {
        $userModel = config('filament-jaga.user_model', \App\Models\User::class);
        $email     = $this->option('email');

        if ($email) {
            $user = $userModel::where('email', $email)->first();

            if (! $user) {
                $this->error("No user found with email: {$email}");
                return self::FAILURE;
            }

            $this->doAssign($user);
            return self::SUCCESS;
        }

        if ($userModel::count() === 0) {
            $this->warn(__('filament-jaga::filament-jaga.install.no_users'));
            return self::SUCCESS;
        }

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $email = $this->ask(__('filament-jaga::filament-jaga.install.enter_email'));
            $user  = $userModel::where('email', $email)->first();

            if ($user) {
                $this->doAssign($user);
                return self::SUCCESS;
            }

            $this->error(__('filament-jaga::filament-jaga.install.user_not_found'));
        }

        $this->error('Maximum attempts reached. Run `php artisan jaga:install --assign --email=<email>` to try again.');
        return self::FAILURE;
    }

    private function doAssign(mixed $user): void
    {
        $role = Role::where('slug', 'super-admin')->firstOrFail();
        $user->roles()->syncWithoutDetaching([$role->id]);
        $this->info(__('filament-jaga::filament-jaga.install.role_assigned', ['email' => $user->email]));
    }
}
