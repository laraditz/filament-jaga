<?php

namespace Laraditz\FilamentJaga\Tests\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laraditz\Jaga\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    protected $guarded = [];
}
