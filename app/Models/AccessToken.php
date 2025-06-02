<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'token' => 'encrypted',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'token',
    ];

    protected static function booted(): void
    {
        static::creating(function (AccessToken $token) {
            // Create a token if no token is provided
            $token->token = $token->token ?: str()->random(64);
        });
    }
}
