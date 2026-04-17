<?php

namespace Laraditz\FilamentJaga\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laraditz\Jaga\Traits\HasRoles;
use Orchestra\Testbench\Factories\UserFactory;

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;

    protected $guarded = [];

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
