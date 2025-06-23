<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserProfileRequest;
use App\Http\Resources\UserProfileCollection;
use App\Http\Resources\UserProfileResource;
use App\Models\UserProfile;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Ramsey\Uuid\Uuid;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('User Profile')]
class UserProfileController extends Controller
{
    /**
     * UserProfile.index
     *
     * Search on UserProfiles
     */
    #[QueryParameter('filter[id]', description: 'Wannabe Profile ID', type: 'int')]
    #[QueryParameter('filter[user_id]', description: 'Wannabe User UUID', type: 'string')]
    #[QueryParameter('filter[email]', description: '', type: 'string')]
    #[QueryParameter('filter[phone]', description: 'In E.164 format. URLencode number first', type: 'string')]
    public function index(Request $request)
    {
        $request->validate([
            /** @ignoreParam */
            'filter.id' => 'int|required_without_all:filter.user_id,filter.email,filter.phone',
            /** @ignoreParam */
            'filter.user_id' => 'uuid|required_without_all:filter.id,filter.email,filter.phone',
            /** @ignoreParam */
            'filter.email' => 'email|required_without_all:filter.id,filter.user_id,filter.phone',
            /** @ignoreParam */
            'filter.phone' => 'required_without_all:filter.id,filter.user_id,filter.email',
        ]);

        $profiles = QueryBuilder::for(UserProfile::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('email'),
                AllowedFilter::exact('phone'),
            ])
            ->get();

        // Check that the user is allowed to view all returned profiles
        // TODO Not fail with 403, just remove the profile from result
        $profiles->each(function (UserProfile $profile) {
            Gate::authorize('view', $profile);
        });

        // Return 404 if no matches
        if ($profiles->count() <= 0) {
            abort(404, 'No query results');
        }

        return new UserProfileCollection($profiles);
    }

    /**
     * UserProfile.show
     *
     * Display the specified profile.
     *
     * @param  UserProfile  $profile  The user UUID or profile ID (wb-id)
     */
    #[QueryParameter('include', description: 'Include additional models', type: 'string', example: 'userCrewHistory')]
    public function show(Request $request, UserProfile $profile)
    {
        $include = $request->string('include');
        $include = collect(explode(',', $include))->flip()->only(['userCrewHistory']);
        $profile->loadMissing($include);

        Gate::authorize('view', $profile);

        return new UserProfileResource($profile);
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

        Gate::authorize('update', $profile);

        return new UserProfileResource($profile);
    }
}
