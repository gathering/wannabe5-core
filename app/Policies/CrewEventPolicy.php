<?php

namespace App\Policies;

use App\Models\CrewEvent;
use App\Models\User;

class CrewEventPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Legacy viewAnyHidden — include non-visible events in listings.
     */
    public function viewAnyHidden(User $user): bool
    {
        return $this->isEventAdmin($user);
    }

    /**
     * Legacy viewAnyInactive — include inactive events in listings.
     */
    public function viewAnyInactive(User $user): bool
    {
        return $this->isEventAdmin($user);
    }

    public function view(User $user, CrewEvent $event): bool
    {
        if ($event->visible) {
            return true;
        }

        return $this->isEventAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isEventAdmin($user);
    }

    public function update(User $user, CrewEvent $event): bool
    {
        return $user->hasAccessAttribute('event:owner', (string) $event->id) || $user->hasAccessAttribute('event:manager', (string) $event->id);
    }

    public function delete(User $user, CrewEvent $event): bool
    {
        return $this->update($user, $event);
    }

    private function isEventAdmin(User $user): bool
    {
        return $user->hasAccessAttribute('event:owner', '*')

            || $user->hasAccessAttribute('event:manager', '*');
    }
}
