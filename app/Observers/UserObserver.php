<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserProfile;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Create profile if missing
        if ($user->type === 'user') {
            $profile = UserProfile::firstOrNew([
                'user_id' => $user->id,
            ]);
            $profile->user_id = $user->id;
            $profile->email = $user->username;
            $profile->save();
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
