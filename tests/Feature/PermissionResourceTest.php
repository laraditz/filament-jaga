<?php

use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\DB;
use Laraditz\FilamentJaga\Resources\PermissionResource;
use Laraditz\FilamentJaga\Resources\PermissionResource\Pages\CreatePermission;
use Laraditz\FilamentJaga\Resources\PermissionResource\Pages\EditPermission;
use Laraditz\FilamentJaga\Resources\PermissionResource\Pages\ListPermissions;
use Laraditz\FilamentJaga\Resources\PermissionResource\RelationManagers\RolesRelationManager;
use Laraditz\FilamentJaga\Resources\PermissionResource\RelationManagers\UsersRelationManager;
use Laraditz\FilamentJaga\Tests\Models\User;
use Laraditz\Jaga\Models\Permission;
use Laraditz\Jaga\Models\Role;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->artisan('jaga:install', ['--email' => $this->user->email]);
    $this->actingAs($this->user);
});

// --- List page ---

it('renders the list page', function () {
    livewire(ListPermissions::class)->assertSuccessful();
});

it('lists permissions', function () {
    Permission::create([
        'name'         => 'posts.index',
        'group'        => 'Posts',
        'methods'      => ['GET'],
        'uri'          => 'posts',
        'description'  => 'List posts',
        'access_level' => 'restricted',
    ]);

    livewire(ListPermissions::class)->assertSee('posts.index');
});

it('renders the create page', function () {
    livewire(CreatePermission::class)->assertSuccessful();
});

it('creates a custom permission', function () {
    livewire(CreatePermission::class)
        ->fillForm([
            'name'         => 'reports.view',
            'group'        => 'Reports',
            'description'  => 'View reports',
            'access_level' => 'restricted',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $permission = Permission::where('name', 'reports.view')->first();
    expect($permission)->not->toBeNull()
        ->and($permission->is_custom)->toBeTrue()
        ->and($permission->group)->toBe('Reports');
});

it('requires name when creating a permission', function () {
    livewire(CreatePermission::class)
        ->fillForm(['name' => '', 'access_level' => 'restricted'])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

it('filters by access level', function () {
    Permission::create([
        'name' => 'public.route', 'group' => 'Public', 'methods' => ['GET'],
        'uri' => 'public', 'access_level' => 'public',
    ]);
    Permission::create([
        'name' => 'auth.route', 'group' => 'Auth', 'methods' => ['GET'],
        'uri' => 'auth-only', 'access_level' => 'auth',
    ]);

    livewire(ListPermissions::class)
        ->filterTable('access_level', 'public')
        ->assertSee('public.route')
        ->assertDontSee('auth.route');
});

it('filters route permissions via tab', function () {
    Permission::create([
        'name' => 'posts.index', 'group' => 'Posts', 'methods' => ['GET'],
        'uri' => 'posts', 'access_level' => 'restricted', 'is_custom' => false,
    ]);
    Permission::create([
        'name' => 'custom.action', 'group' => 'Custom', 'methods' => ['GET'],
        'uri' => '', 'access_level' => 'restricted', 'is_custom' => true,
    ]);

    livewire(ListPermissions::class)
        ->set('activeTab', 'route')
        ->assertSee('posts.index')
        ->assertDontSee('custom.action');
});

it('filters custom permissions via tab', function () {
    Permission::create([
        'name' => 'posts.index', 'group' => 'Posts', 'methods' => ['GET'],
        'uri' => 'posts', 'access_level' => 'restricted', 'is_custom' => false,
    ]);
    Permission::create([
        'name' => 'custom.action', 'group' => 'Custom', 'methods' => ['GET'],
        'uri' => '', 'access_level' => 'restricted', 'is_custom' => true,
    ]);

    livewire(ListPermissions::class)
        ->set('activeTab', 'custom')
        ->assertSee('custom.action')
        ->assertDontSee('posts.index');
});

it('can delete a custom permission', function () {
    $permission = Permission::create([
        'name' => 'reports.delete', 'group' => 'Reports', 'methods' => ['DELETE'],
        'uri' => '', 'access_level' => 'restricted', 'is_custom' => true,
    ]);

    livewire(EditPermission::class, ['record' => $permission->id])
        ->callAction(DeleteAction::class)
        ->assertRedirect();

    expect(Permission::find($permission->id))->toBeNull();
});

it('cannot delete a route permission', function () {
    $permission = Permission::create([
        'name' => 'posts.index', 'group' => 'Posts', 'methods' => ['GET'],
        'uri' => 'posts', 'access_level' => 'restricted', 'is_custom' => false,
    ]);

    expect(PermissionResource::canDelete($permission))->toBeFalse();
});

// --- Sync action ---

it('has a sync permissions header action', function () {
    livewire(ListPermissions::class)
        ->assertActionExists('sync');
});

it('dispatches SyncPermissionsJob and notifies when sync is confirmed', function () {
    \Illuminate\Support\Facades\Queue::fake();

    livewire(ListPermissions::class)
        ->callAction('sync')
        ->assertNotified();

    \Illuminate\Support\Facades\Queue::assertPushed(\Laraditz\Jaga\Jobs\SyncPermissionsJob::class);
});

// --- Edit page ---

it('renders the edit page', function () {
    $permission = Permission::create([
        'name'         => 'posts.index',
        'group'        => 'Posts',
        'methods'      => ['GET'],
        'uri'          => 'posts',
        'description'  => 'List posts',
        'access_level' => 'restricted',
    ]);

    livewire(EditPermission::class, ['record' => $permission->id])
        ->assertSuccessful();
});

it('saves editable fields on edit', function () {
    $permission = Permission::create([
        'name'         => 'posts.index',
        'group'        => 'Posts',
        'methods'      => ['GET'],
        'uri'          => 'posts',
        'description'  => 'List posts',
        'access_level' => 'restricted',
    ]);

    livewire(EditPermission::class, ['record' => $permission->id])
        ->fillForm([
            'group'        => 'Articles',
            'description'  => 'Show all articles',
            'access_level' => 'auth',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $permission->refresh();
    expect($permission->group)->toBe('Articles')
        ->and($permission->description)->toBe('Show all articles')
        ->and($permission->access_level->value)->toBe('auth');
});

it('does not change name or uri on edit save', function () {
    $permission = Permission::create([
        'name'         => 'posts.index',
        'group'        => 'Posts',
        'methods'      => ['GET'],
        'uri'          => 'posts',
        'access_level' => 'restricted',
    ]);

    livewire(EditPermission::class, ['record' => $permission->id])
        ->fillForm(['group' => 'Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    $permission->refresh();
    expect($permission->name)->toBe('posts.index')
        ->and($permission->uri)->toBe('posts');
});

// --- RolesRelationManager ---

it('can attach a role to a permission', function () {
    $permission = Permission::create([
        'name' => 'posts.index', 'group' => 'Posts', 'methods' => ['GET'],
        'uri' => 'posts', 'access_level' => 'restricted',
    ]);
    $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'guard_name' => 'web']);

    livewire(RolesRelationManager::class, [
        'ownerRecord' => $permission,
        'pageClass'   => EditPermission::class,
    ])
        ->callTableAction('attach', data: ['recordId' => $role->id])
        ->assertHasNoTableActionErrors();

    expect(
        DB::table(config('jaga.tables.role_permission'))
            ->where('permission_id', $permission->id)
            ->where('role_id', $role->id)
            ->exists()
    )->toBeTrue();
});

it('can detach a role from a permission', function () {
    $permission = Permission::create([
        'name' => 'posts.index', 'group' => 'Posts', 'methods' => ['GET'],
        'uri' => 'posts', 'access_level' => 'restricted',
    ]);
    $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'guard_name' => 'web']);

    DB::table(config('jaga.tables.role_permission'))->insert([
        'role_id'       => $role->id,
        'permission_id' => $permission->id,
        'created_at'    => now(),
    ]);

    livewire(RolesRelationManager::class, [
        'ownerRecord' => $permission,
        'pageClass'   => EditPermission::class,
    ])
        ->callTableAction('detach', $role)
        ->assertHasNoTableActionErrors();

    expect(
        DB::table(config('jaga.tables.role_permission'))
            ->where('permission_id', $permission->id)
            ->where('role_id', $role->id)
            ->exists()
    )->toBeFalse();
});

// --- UsersRelationManager ---

it('can attach a user to a permission', function () {
    $permission = Permission::create([
        'name' => 'posts.index', 'group' => 'Posts', 'methods' => ['GET'],
        'uri' => 'posts', 'access_level' => 'restricted',
    ]);
    $otherUser = User::factory()->create();

    livewire(UsersRelationManager::class, [
        'ownerRecord' => $permission,
        'pageClass'   => EditPermission::class,
    ])
        ->callTableAction('attach', data: ['user_id' => $otherUser->id])
        ->assertHasNoTableActionErrors();

    expect(
        DB::table(config('jaga.tables.model_permission'))
            ->where('permission_id', $permission->id)
            ->where('model_id', $otherUser->id)
            ->where('model_type', User::class)
            ->exists()
    )->toBeTrue();
});

it('can detach a user from a permission', function () {
    $permission = Permission::create([
        'name' => 'posts.index', 'group' => 'Posts', 'methods' => ['GET'],
        'uri' => 'posts', 'access_level' => 'restricted',
    ]);
    $otherUser = User::factory()->create();

    DB::table(config('jaga.tables.model_permission'))->insert([
        'model_type'    => User::class,
        'model_id'      => $otherUser->id,
        'permission_id' => $permission->id,
        'created_at'    => now(),
    ]);

    livewire(UsersRelationManager::class, [
        'ownerRecord' => $permission,
        'pageClass'   => EditPermission::class,
    ])
        ->callTableAction('detach', $otherUser)
        ->assertHasNoTableActionErrors();

    expect(
        DB::table(config('jaga.tables.model_permission'))
            ->where('permission_id', $permission->id)
            ->where('model_id', $otherUser->id)
            ->exists()
    )->toBeFalse();
});
