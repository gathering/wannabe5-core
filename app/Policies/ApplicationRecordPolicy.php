<?php

namespace App\Policies;

use App\Models\ApplicationRecord;
use App\Models\User;

class ApplicationRecordPolicy
{
    public function viewAny(User $user, int $crew): bool
    {
        return $user->hasAccessAttribute('crew:owner', (string) $crew)
            || $user->hasAccessAttribute('crew:manager', (string) $crew)
            || $user->hasAccessAttribute('crew:applications:manager', (string) $crew);
    }

    public function view(User $user, ApplicationRecord $application): bool
    {
        return $user->id === $application->user_id || $this->viewAny($user, $application->crew_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ApplicationRecord $application): bool
    {
        return $user->id === $application->user_id;
    }

    public function delete(User $user, ApplicationRecord $application): bool
    {
        return $user->id === $application->user_id;
    }

    public function updateStatus(User $user, ApplicationRecord $application): bool
    {
        return $this->viewAny($user, $application->crew_id);
    }

    public function comment(User $user, ApplicationRecord $application): bool
    {
        return $this->viewAny($user, $application->crew_id);
    }
}
