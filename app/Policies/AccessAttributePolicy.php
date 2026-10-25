<?php

namespace App\Policies;

use App\Models\AccessAttribute;
use App\Models\User;

class AccessAttributePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AccessAttribute $attribute): bool
    {
        return $user->id === $attribute->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AccessAttribute $attribute): bool
    {
        return $user->id === $attribute->user_id;
    }

    public function delete(User $user, AccessAttribute $attribute): bool
    {
        return $user->id === $attribute->user_id;
    }
}
