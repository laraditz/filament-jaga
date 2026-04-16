<?php

use Illuminate\Support\Facades\DB;
use Laraditz\Jaga\Models\Permission;
use Laraditz\Jaga\Models\Role;

it('seeds the jaga.access permission with is_custom=true', function () {
    $this->artisan('jaga:install')->assertSuccessful();

    $permission = Permission::where('name', 'jaga.access')->first();
    expect($permission)->not->toBeNull();
    expect($permission->is_custom)->toBeTrue();
});

it('creates a super-admin role', function () {
    $this->artisan('jaga:install')->assertSuccessful();

    $role = Role::where('slug', 'super-admin')->first();
    expect($role)->not->toBeNull();
});

it('assigns jaga.access and wildcard * to the super-admin role', function () {
    $this->artisan('jaga:install')->assertSuccessful();

    $role       = Role::where('slug', 'super-admin')->firstOrFail();
    $permission = Permission::where('name', 'jaga.access')->firstOrFail();

    expect(
        DB::table('role_permission')
            ->where('role_id', $role->id)
            ->where('permission_id', $permission->id)
            ->exists()
    )->toBeTrue();

    expect(
        DB::table('role_permission')
            ->where('role_id', $role->id)
            ->where('wildcard', '*')
            ->exists()
    )->toBeTrue();
});

it('is idempotent — running twice does not duplicate records', function () {
    $this->artisan('jaga:install')->assertSuccessful();
    $this->artisan('jaga:install')->assertSuccessful();

    expect(Permission::where('name', 'jaga.access')->count())->toBe(1);
    expect(Role::where('slug', 'super-admin')->count())->toBe(1);
});

it('notifies when no users exist and skips user assignment', function () {
    $this->artisan('jaga:install')
        ->expectsOutputToContain('No users found')
        ->assertSuccessful();
});

it('assigns super-admin role to user when --email is provided', function () {
    $user = \Orchestra\Testbench\Factories\UserFactory::new()->create(['email' => 'admin@example.com']);

    $this->artisan('jaga:install', ['--email' => 'admin@example.com'])
        ->assertSuccessful();

    $role = Role::where('slug', 'super-admin')->firstOrFail();
    expect(
        DB::table('model_role')
            ->where('model_type', get_class($user))
            ->where('model_id', $user->id)
            ->where('role_id', $role->id)
            ->exists()
    )->toBeTrue();
});

it('--assign flag skips seeding and only assigns user', function () {
    $this->artisan('jaga:install')->assertSuccessful();

    $user = \Orchestra\Testbench\Factories\UserFactory::new()->create(['email' => 'admin@example.com']);

    $this->artisan('jaga:install', ['--assign' => true, '--email' => 'admin@example.com'])
        ->assertSuccessful();

    $role = Role::where('slug', 'super-admin')->firstOrFail();
    expect(
        DB::table('model_role')
            ->where('model_type', get_class($user))
            ->where('model_id', $user->id)
            ->where('role_id', $role->id)
            ->exists()
    )->toBeTrue();
});

it('reports error when --email user does not exist', function () {
    $this->artisan('jaga:install', ['--email' => 'nobody@example.com'])
        ->expectsOutputToContain('No user found')
        ->assertFailed();
});
