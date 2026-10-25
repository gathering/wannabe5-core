<?php

namespace App\Models;

use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[ObservedBy([UserObserver::class])]
class User extends Authenticatable
{
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'username', 'type'];

    public function accessTokens()
    {
        return $this->hasMany(AccessToken::class, 'user_id', 'id');
    }

    public function userProfile()
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    public function accessAttributes()
    {
        return $this->hasMany(AccessAttribute::class, 'user_id', 'id');
    }

    /**
     * Whether this user holds an access attribute key with the given value (or '*').
     */
    public function hasAccessAttribute(string $key, string $value = '*'): bool
    {
        $this->loadMissing('accessAttributes');

        foreach ($this->accessAttributes as $attribute) {
            if ((string) $attribute->key !== $key) {
                continue;
            }

            $values = $attribute->value;
            if (! is_array($values)) {
                $values = [$values];
            }

            if (in_array('*', $values, true) || in_array($value, $values, true)) {
                return true;
            }
        }

        return false;
    }
}
