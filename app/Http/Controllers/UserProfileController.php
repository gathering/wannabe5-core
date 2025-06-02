<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserProfileRequest;
use App\Http\Resources\UserProfileCollection;
use App\Http\Resources\UserProfileResource;
use App\Models\UserProfile;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

#[Group('User Profile')]
class UserProfileController extends Controller
{
    /**
     * UserProfile.show
     *
     * Display the specified profile.
     *
     * @param  UserProfile  $profile  The user UUID or profile ID (wb-id)
     */
    public function show(Request $request, UserProfile $profile)
    {
        /**
         * Include additional models
         *
         * @example userCrewHistory
         */
        $include = $request->string('include');
        $include = collect(explode(',', $include))->flip()->only(['userCrewHistory']);
        $profile->loadMissing($include);

        return new UserProfileResource($profile);
    }

    /**
     * UserProfile.search
     *
     * Search on UserProfiles
     */
    #[QueryParameter('name', description: 'Firstname and/or lastname', type: 'string')]
    #[QueryParameter('email', type: 'string')]
    #[QueryParameter('phone', type: 'string')]
    #[QueryParameter('nickname', type: 'string')]
    public function search(Request $request)
    {
        $fields = ['name', 'email', 'phone', 'nickname'];
        $query = [];
        foreach ($fields as $x) {
            $query[$x] = $request->string($x)->value ?: null;
        }

        return new UserProfileCollection(UserProfile::where(array_filter($query))->get());
    }

    /**
     * UserProfile.update
     *
     * Update the specified profile.
     *
     * @param  UserProfile  $profile  The user UUID or profile ID (wb-id)
     */
    public function update(UserProfileRequest $request, UserProfile $profile)
    {
        $validated = $request->safe();
        $profile->update($validated->only(['nickname', 'birthdate', 'phone', 'gender']));
        $profile->update($validated->address);

        return new UserProfileResource($profile);
    }
}
