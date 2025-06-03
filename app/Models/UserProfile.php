<?php

namespace App\Models;

use App\Casts\Postcode;
use App\Enums\UserProfileGender;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'nickname', 'birthdate', 'phone', 'gender',
        'streetaddress', 'town', 'postcode', 'countrycode',
    ];

    public $casts = [
        'postcode' => Postcode::class,
        'phone' => E164PhoneNumberCast::class.':NO',
        'gender' => UserProfileGender::class,
    ];

    public function userCrewHistory()
    {
        return $this->hasMany(UserCrewHistory::class, 'user_id', 'user_id');
    }
}
