<?php

namespace App\Policies;

use App\Models\AccessAttribute;
use App\Models\User;

class AccessAttributePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAccessAttribute('access:owner')
            || $user->hasAccessAttribute('access:reader')
            || $user->hasAccessAttribute('user:owner');
    }

    public function view(User $user, AccessAttribute $attribute): bool
    {
        return $user->hasAccessAttribute('access:owner', (string) $attribute->id)
            || $user->hasAccessAttribute('access:reader', (string) $attribute->id)
            || $user->hasAccessAttribute('user:owner', (string) $attribute->id);
    }

    public function create(User $user): bool
    {
        return $user->hasAccessAttribute('access:owner');
    }

    public function update(User $user, AccessAttribute $attribute): bool
    {
        return $user->hasAccessAttribute('access:owner', (string) $attribute->id);
    }

    public function delete(User $user, AccessAttribute $attribute): bool
    {
        return $user->hasAccessAttribute('access:owner', (string) $attribute->id);
    }
}
