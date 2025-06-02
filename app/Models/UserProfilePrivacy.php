<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfilePrivacy extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'phone', 'address', 'birthdate', 'ice',
    ];

    /**
     * Default attributes.
     *
     * @var array
     */
    protected $attributes = [
        'phone' => 'crew',
        'address' => 'needtoknow',
        'birthdate' => 'crew',
        'ice' => 'needtoknow',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'user_id',
    ];
}
