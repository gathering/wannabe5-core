<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    public function accessTokens()
    {
        return $this->hasMany(AccessToken::class, 'user_id', 'id');
    }
}
