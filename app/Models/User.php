<?php

namespace App\Models;

use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[ObservedBy([UserObserver::class])]
class User extends Authenticatable
{
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'username', 'type'];

    /** @return HasMany<AccessToken, $this> */
    public function accessTokens(): HasMany
    {
        return $this->hasMany(AccessToken::class, 'user_id', 'id');
    }

    /** @return HasOne<UserProfile, $this> */
    public function userProfile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    /** @return HasMany<AccessAttribute, $this> */
    public function accessAttributes(): HasMany
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
