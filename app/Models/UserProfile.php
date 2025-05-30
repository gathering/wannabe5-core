<?php

namespace App\Models;

use App\Enums\UserProfileGender;
use Illuminate\Database\Eloquent\Model;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;

class UserProfile extends Model
{
    protected $fillable = [
        'nickname', 'birth', 'phone', 'gender',
    ];

    public $casts = [
        'phone' => E164PhoneNumberCast::class.':NO',
        'gender' => UserProfileGender::class,
    ];
}
