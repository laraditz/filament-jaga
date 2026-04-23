<?php

use Laraditz\FilamentJaga\Tests\Models\User;
use Laraditz\FilamentJaga\Tests\Support\UserResource\Pages\EditUser;
use Laraditz\Jaga\Models\Role;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->artisan('jaga:install', ['--email' => $this->user->email]);
    $this->actingAs($this->user);
});

it('hydrates existing roles into field state', function () {
    $target = User::factory()->create();
    $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'guard_name' => 'web']);
    $target->assignRole($role);

    livewire(EditUser::class, ['record' => $target->id])
        ->assertFormFieldExists('jaga_roles')
        ->assertSet('data.jaga_roles', fn ($v) => in_array($role->id, (array) $v));
});

it('assigns a new role when saving', function () {
    $target = User::factory()->create();
    $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'guard_name' => 'web']);

    livewire(EditUser::class, ['record' => $target->id])
        ->fillForm(['jaga_roles' => [$role->id]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->hasRole('editor'))->toBeTrue();
});

it('removes a deselected role when saving', function () {
    $target = User::factory()->create();
    $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'guard_name' => 'web']);
    $target->assignRole($role);

    livewire(EditUser::class, ['record' => $target->id])
        ->fillForm(['jaga_roles' => []])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->hasRole('editor'))->toBeFalse();
});
