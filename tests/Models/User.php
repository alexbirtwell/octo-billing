<?php

namespace OctoBilling\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Cashier\Billable;
use OctoBilling\Tests\database\factories\UserFactory;

class User extends Authenticatable
{
    use Billable;
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
