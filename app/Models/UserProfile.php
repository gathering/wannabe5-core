<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;

class UserProfile extends Model
{
    public const private = 4;

    public const needtoknow = 3;

    public const crew = 2;

    public const public = 0;

    protected $fillable = [
        'nickname', 'birth', 'phone', 'sexe', 'image', 'language',
        // Owned by account, read-only in profile service
        // 'name', 'email'
    ];

    public $casts = [
        'phone' => E164PhoneNumberCast::class.':NO',
    ];
}
