<?php

namespace App\Domain\Crew\Services;

use App\Models\CrewUser;
use App\Models\ProfileImage;
use App\Models\UserProfile;
use Illuminate\Support\Collection;

class CrewMembershipPresenter
{
    /**
     * Attach profile (+ optional latest image) for frontend crew/member UIs.
     *
     * @param  Collection<int, CrewUser>|iterable<int, CrewUser>  $memberships
     * @return list<array<string, mixed>>
     */
    public function presentMany(iterable $memberships): array
    {
        $rows = Collection::make($memberships)->values();
        if ($rows->isEmpty()) {
            return [];
        }

        $userIds = $rows->pluck('user_id')->unique()->filter()->values()->all();
        $profiles = UserProfile::query()
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        $images = ProfileImage::query()
            ->whereIn('profile_id', $profiles->pluck('id')->all())
            ->latest('id')
            ->get()
            ->unique('profile_id')
            ->keyBy('profile_id');

        return $rows->map(function (CrewUser $membership) use ($profiles, $images): array {
            return $this->presentOne($membership, $profiles->get($membership->user_id), $images);
        })->all();
    }

    /**
     * @param  Collection<int, ProfileImage>  $imagesByProfileId
     * @return array<string, mixed>
     */
    public function presentOne(CrewUser $membership, ?UserProfile $profile = null, ?Collection $imagesByProfileId = null): array
    {
        $membership->loadMissing(['crew', 'role', 'team']);
        $profile ??= UserProfile::query()->where('user_id', $membership->user_id)->first();

        $payload = $membership->toArray();
        $payload['profile'] = $this->presentProfile($profile, $imagesByProfileId);

        return $payload;
    }

    /**
     * @param  Collection<int, ProfileImage>|null  $imagesByProfileId
     * @return array<string, mixed>|null
     */
    private function presentProfile(?UserProfile $profile, ?Collection $imagesByProfileId): ?array
    {
        if ($profile === null) {
            return null;
        }

        $image = null;
        if ($imagesByProfileId !== null) {
            $image = $imagesByProfileId->get($profile->id);
        } else {
            $image = ProfileImage::query()->where('profile_id', $profile->id)->latest('id')->first();
        }

        $data = $profile->toArray();
        $data['name'] = trim(implode(' ', array_filter([(string) $profile->firstname, (string) $profile->lastname]))) ?: null;
        $data['profile_image'] = $image;

        return $data;
    }
}
