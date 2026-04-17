<?php

use Illuminate\Support\Facades\DB;
use Laraditz\FilamentJaga\Resources\RoleResource;
use Laraditz\FilamentJaga\Resources\RoleResource\Pages\CreateRole;
use Laraditz\FilamentJaga\Resources\RoleResource\Pages\EditRole;
use Laraditz\FilamentJaga\Resources\RoleResource\Pages\ListRoles;
use Laraditz\FilamentJaga\Tests\Models\User;
use Laraditz\Jaga\Models\Permission;
use Laraditz\Jaga\Models\Role;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->artisan('jaga:install', ['--email' => $this->user->email]);
    $this->actingAs($this->user);
});

it('renders the list page', function () {
    livewire(ListRoles::class)->assertSuccessful();
});

it('lists roles', function () {
    Role::create(['name' => 'Editor', 'slug' => 'editor', 'guard_name' => 'web']);

    livewire(ListRoles::class)->assertSee('Editor');
});

it('renders the create page', function () {
    livewire(CreateRole::class)->assertSuccessful();
});

it('creates a role with a name', function () {
    livewire(CreateRole::class)
        ->fillForm(['name' => 'Moderator', 'slug' => 'moderator'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Role::where('slug', 'moderator')->exists())->toBeTrue();
});

it('auto-generates a slug from the name', function () {
    livewire(CreateRole::class)
        ->fillForm(['name' => 'Content Editor', 'slug' => 'content-editor'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Role::where('slug', 'content-editor')->exists())->toBeTrue();
});

it('assigns permissions to a role on create', function () {
    $permission = Permission::create([
        'name'        => 'posts.index',
        'group'       => 'Posts',
        'methods'     => ['GET'],
        'uri'         => 'posts',
        'description' => 'List posts',
        'access_level' => 'restricted',
    ]);

    livewire(CreateRole::class)
        ->fillForm([
            'name'             => 'Writer',
            'slug'             => 'writer',
            'permissions_posts' => [$permission->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Role::where('slug', 'writer')->firstOrFail();
    expect($role->permissions->pluck('name')->contains('posts.index'))->toBeTrue();
});

it('saves wildcard patterns on create', function () {
    livewire(CreateRole::class)
        ->fillForm([
            'name'              => 'Reports Viewer',
            'slug'              => 'reports-viewer',
            'wildcard_patterns' => [['pattern' => 'reports.*']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $role = Role::where('slug', 'reports-viewer')->firstOrFail();

    expect(
        DB::table('role_permission')
            ->where('role_id', $role->id)
            ->whereNull('permission_id')
            ->where('wildcard', 'reports.*')
            ->exists()
    )->toBeTrue();
});

it('replaces wildcard patterns on edit (full replace)', function () {
    $role = Role::create(['name' => 'Admin', 'slug' => 'admin', 'guard_name' => 'web']);
    DB::table('role_permission')->insert([
        'role_id' => $role->id, 'permission_id' => null, 'wildcard' => 'old.*', 'created_at' => now(),
    ]);

    livewire(EditRole::class, ['record' => $role->getRouteKey()])
        ->fillForm(['wildcard_patterns' => [['pattern' => 'new.*']]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(DB::table('role_permission')->where('role_id', $role->id)->where('wildcard', 'old.*')->exists())->toBeFalse();
    expect(DB::table('role_permission')->where('role_id', $role->id)->where('wildcard', 'new.*')->exists())->toBeTrue();
});

it('deletes a role', function () {
    $role = Role::create(['name' => 'Temp', 'slug' => 'temp', 'guard_name' => 'web']);

    livewire(ListRoles::class)
        ->callTableAction('delete', $role)
        ->assertSuccessful();

    expect(Role::find($role->id))->toBeNull();
});
