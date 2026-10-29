<?php

namespace App\Policies;

use App\Models\Crew;
use App\Models\User;

class CrewPolicy
{
    public function view(User $user, Crew $crew): bool
    {
        foreach (['owner', 'manager', 'member', 'reader'] as $role) {
            if ($user->hasAccessAttribute('crew:'.$role, (string) $crew->id)
                || $user->hasAccessAttribute('event:'.$role, (string) $crew->event_id)) {
                return true;
            }
        }

        return false;
    }

    public function update(User $user, Crew $crew): bool
    {
        return $user->hasAccessAttribute('crew:owner', (string) $crew->id)
            || $user->hasAccessAttribute('crew:manager', (string) $crew->id)
            || $this->delete($user, $crew);
    }

    public function delete(User $user, Crew $crew): bool
    {
        return $user->hasAccessAttribute('event:owner', (string) $crew->event_id)
            || $user->hasAccessAttribute('event:manager', (string) $crew->event_id);
    }
}
