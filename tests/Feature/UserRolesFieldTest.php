<?php

use Illuminate\Support\Facades\DB;
use Laraditz\FilamentJaga\Tests\Models\User;
use Laraditz\FilamentJaga\Tests\Support\UserResource\Pages\EditUser;
use Laraditz\Jaga\Models\Permission;
use Laraditz\Jaga\Models\Role;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->artisan('jaga:install', ['--email' => $this->user->email]);
    $this->actingAs($this->user);
});

it('hydrates existing roles and permissions into field state', function () {
    $target = User::factory()->create();
    $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'guard_name' => 'web']);
    $permission = Permission::create([
        'name' => 'posts.index', 'group' => 'Posts',
        'methods' => ['GET'], 'uri' => 'posts', 'access_level' => 'restricted',
    ]);
    $target->assignRole($role);
    $target->grantPermission($permission->id);

    livewire(EditUser::class, ['record' => $target->id])
        ->assertFormFieldExists('jaga_roles')
        ->assertSet('data.jaga_roles.roles', fn ($v) => in_array($role->id, (array) $v))
        ->assertSet('data.jaga_roles.permissions', fn ($v) => in_array($permission->id, (array) $v));
});

it('assigns a new role when saving', function () {
    $target = User::factory()->create();
    $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'guard_name' => 'web']);

    livewire(EditUser::class, ['record' => $target->id])
        ->fillForm(['jaga_roles' => ['roles' => [$role->id], 'permissions' => []]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->hasRole('editor'))->toBeTrue();
});

it('removes a deselected role when saving', function () {
    $target = User::factory()->create();
    $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'guard_name' => 'web']);
    $target->assignRole($role);

    livewire(EditUser::class, ['record' => $target->id])
        ->fillForm(['jaga_roles' => ['roles' => [], 'permissions' => []]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->hasRole('editor'))->toBeFalse();
});

it('grants a new direct permission when saving', function () {
    $target = User::factory()->create();
    $permission = Permission::create([
        'name' => 'posts.index', 'group' => 'Posts',
        'methods' => ['GET'], 'uri' => 'posts', 'access_level' => 'restricted',
    ]);

    livewire(EditUser::class, ['record' => $target->id])
        ->fillForm(['jaga_roles' => ['roles' => [], 'permissions' => [$permission->id]]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(
        DB::table(config('jaga.tables.model_permission'))
            ->where('model_type', User::class)
            ->where('model_id', $target->id)
            ->where('permission_id', $permission->id)
            ->exists()
    )->toBeTrue();
});

it('revokes a removed direct permission when saving', function () {
    $target = User::factory()->create();
    $permission = Permission::create([
        'name' => 'posts.index', 'group' => 'Posts',
        'methods' => ['GET'], 'uri' => 'posts', 'access_level' => 'restricted',
    ]);
    $target->grantPermission($permission->id);

    livewire(EditUser::class, ['record' => $target->id])
        ->fillForm(['jaga_roles' => ['roles' => [], 'permissions' => []]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(
        DB::table(config('jaga.tables.model_permission'))
            ->where('model_type', User::class)
            ->where('model_id', $target->id)
            ->where('permission_id', $permission->id)
            ->exists()
    )->toBeFalse();
});

it('does not create duplicate permission rows on no-op save', function () {
    $target = User::factory()->create();
    $permission = Permission::create([
        'name' => 'posts.index', 'group' => 'Posts',
        'methods' => ['GET'], 'uri' => 'posts', 'access_level' => 'restricted',
    ]);
    $target->grantPermission($permission->id);

    $before = DB::table(config('jaga.tables.model_permission'))
        ->where('model_type', User::class)
        ->where('model_id', $target->id)
        ->count();

    livewire(EditUser::class, ['record' => $target->id])
        ->fillForm(['jaga_roles' => ['roles' => [], 'permissions' => [$permission->id]]])
        ->call('save')
        ->assertHasNoFormErrors();

    $after = DB::table(config('jaga.tables.model_permission'))
        ->where('model_type', User::class)
        ->where('model_id', $target->id)
        ->count();

    expect($after)->toBe($before);
});
