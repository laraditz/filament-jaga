<?php

use Laraditz\FilamentJaga\Resources\PermissionResource;
use Laraditz\FilamentJaga\Resources\PermissionResource\Pages\ListPermissions;
use Laraditz\FilamentJaga\Tests\Models\User;
use Laraditz\Jaga\Models\Permission;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->artisan('jaga:install', ['--email' => $this->user->email]);
    $this->actingAs($this->user);
});

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

it('cannot create permissions via the resource', function () {
    expect(PermissionResource::canCreate())->toBeFalse();
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
