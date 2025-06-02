<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserProfileResource;
use App\Models\UserProfile;
use Dedoc\Scramble\Attributes\Group;

#[Group('User Profile')]
class UserProfileController extends Controller
{
    /**
     * UserProfile.Index
     *
     * This is a description. In can be as large as needed and contain `markdown`.
     */
    public function index()
    {
        return new UserProfileResource(
            UserProfile::where('user_id', auth()->user()->id)->firstOrFail()
        );
    }
}
