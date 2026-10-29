<?php

namespace App\Policies;

use App\Models\AccessAttributeType;
use App\Models\User;

class AccessAttributeTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAccessAttribute('access:owner')
            || $user->hasAccessAttribute('access:reader')
            || $user->hasAccessAttribute('user:owner');
    }

    public function view(User $user, AccessAttributeType $type): bool
    {
        return $user->hasAccessAttribute('access:owner', (string) $type->id)
            || $user->hasAccessAttribute('access:reader', (string) $type->id);
    }

    public function create(User $user): bool
    {
        return $user->hasAccessAttribute('access:owner');
    }

    public function update(User $user, AccessAttributeType $type): bool
    {
        return $user->hasAccessAttribute('access:owner', (string) $type->id);
    }

    public function delete(User $user, AccessAttributeType $type): bool
    {
        return $this->update($user, $type);
    }
}
