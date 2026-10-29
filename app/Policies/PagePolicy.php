<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    public function view(User $user, Page $page): bool
    {
        return $page->public || $page->author_id === $user->id || $this->create($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAccessAttribute('event:owner') || $user->hasAccessAttribute('event:manager');
    }

    public function update(User $user, Page $page): bool
    {
        return $page->author_id === $user->id || $this->create($user);
    }

    public function delete(User $user, Page $page): bool
    {
        return $this->update($user, $page);
    }
}
