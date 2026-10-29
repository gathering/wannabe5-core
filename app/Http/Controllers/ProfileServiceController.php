<?php

namespace App\Http\Controllers;

use App\Domain\Profile\Services\ProfileImageProcessor;
use App\Models\ProfileImage;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ProfileServiceController extends Controller
{
    public function __construct(
        protected ProfileImageProcessor $imageProcessor,
    ) {}

    public function root()
    {
        return response()->json(['version' => app()->version()]);
    }

    public function search(Request $request)
    {
        $query = UserProfile::query();
        if ($request->filled('nickname')) {
            $query->where('nickname', 'like', '%'.$request->string('nickname').'%');
        }
        if ($request->filled('email')) {
            $userIds = User::query()->where('username', $request->string('email'))->pluck('id');
            $query->whereIn('user_id', $userIds);
        }

        return response()->json($query->limit(50)->get()->filter(fn ($profile) => Gate::allows('view', $profile))->values());
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'nickname' => ['nullable', 'string'],
            'birthdate' => ['nullable', 'date'],
            'phone' => ['nullable', 'string'],
            'gender' => ['nullable', 'string'],
        ]);

        abort_unless($request->user()->id === $validated['user_id'], 403);

        $profile = UserProfile::query()->firstOrCreate(
            ['user_id' => $validated['user_id']],
            $validated
        );

        return response()->json($profile, 201);
    }

    public function show(int|string $id)
    {
        $profile = $this->findProfile($id);
        Gate::authorize('view', $profile);

        return response()->json($profile);
    }

    public function update(Request $request, int|string $id)
    {
        $profile = $this->findProfile($id);
        Gate::authorize('update', $profile);
        $profile->update($request->only([
            'nickname', 'birthdate', 'phone', 'gender', 'streetaddress', 'town', 'postcode', 'countrycode',
        ]));

        return response()->json($profile->refresh());
    }

    public function delete(int|string $id)
    {
        $profile = $this->findProfile($id);
        Gate::authorize('update', $profile);
        $profile->delete();

        return response()->json(true);
    }

    public function showCrewHistory(int|string $id)
    {
        $profile = $this->findProfile($id);
        Gate::authorize('view', $profile);

        return response()->json($profile->userCrewHistory()->get());
    }

    public function updateFromAuth(Request $request)
    {
        $user = $request->user();
        abort_if($user === null, 401);
        $profile = $user->userProfile;
        if ($profile === null) {
            $profile = UserProfile::create(['user_id' => $user->id]);
        }

        return response()->json([
            'status' => 'synced',
            'profile_id' => $profile->id,
            'user_id' => $user->id,
        ]);
    }

    public function showImage(int $profileId)
    {
        Gate::authorize('view', $this->findProfile($profileId));

        return response()->json(
            ProfileImage::query()->where('profile_id', $profileId)->latest()->firstOrFail()
        );
    }

    public function createImage(Request $request, int $profileId)
    {
        Gate::authorize('update', $this->findProfile($profileId));

        $this->findProfile($profileId);
        $request->validate([
            'image' => ['nullable', 'image', 'max:10240'],
            'profile_picture_file' => ['nullable', 'image', 'max:10240'],
        ]);

        $this->imageProcessor->validateCrop($request->validate(['crop' => ['sometimes', 'array']])['crop'] ?? []);
        $upload = $request->file('profile_picture_file') ?? $request->file('image');
        abort_if($upload === null, 422, 'Profile picture file is missing');

        $existing = ProfileImage::query()->where('profile_id', $profileId)->latest()->first();
        if ($existing !== null && $existing->approval_state === 'approved') {
            return response()->json([
                'error' => 'Cannot upload new picture when existing picture is approved',
            ], 403);
        }

        $path = $this->imageProcessor->storeResized($upload);
        if ($existing !== null) {
            if ($existing->path) {
                Storage::disk('local')->delete($existing->path);
            }
            $existing->update([
                'path' => $path,
                'approval_state' => 'new',
                'decline_reason' => null,
                'crop' => [],
            ]);

            return response()->json($existing->refresh());
        }

        $image = ProfileImage::create([
            'profile_id' => $profileId,
            'path' => $path,
            'approval_state' => 'new',
            'crop' => [],
        ]);

        return response()->json($image, 201);
    }

    public function updateImage(Request $request, int $profileId)
    {
        if (! $request->exists('approval_state') && ! $request->exists('decline_reason')) {
            Gate::authorize('update', $this->findProfile($profileId));
        }
        if ($request->exists('approval_state') || $request->exists('decline_reason')) {
            $this->authorizeModeration();
        }
        if ($request->hasFile('image') || $request->hasFile('profile_picture_file') || $request->exists('crop')) {
            Gate::authorize('update', $this->findProfile($profileId));
        }

        $image = ProfileImage::query()->where('profile_id', $profileId)->latest()->firstOrFail();
        $request->validate([
            'image' => ['nullable', 'image', 'max:10240'],
            'profile_picture_file' => ['nullable', 'image', 'max:10240'],
            'approval_state' => ['nullable', 'in:new,approved,declined'],
            'decline_reason' => ['nullable', 'string'],
            'crop' => ['nullable', 'array'],
        ]);

        $this->imageProcessor->validateCrop($request->validate(['crop' => ['sometimes', 'array']])['crop'] ?? []);
        $upload = $request->file('profile_picture_file') ?? $request->file('image');
        if ($upload !== null) {
            if ($image->approval_state === 'approved') {
                return response()->json([
                    'error' => 'Cannot upload new picture when existing picture is approved',
                ], 403);
            }
            if ($image->path) {
                Storage::disk('local')->delete($image->path);
            }
            $image->path = $this->imageProcessor->storeResized($upload);
            $image->approval_state = 'new';
            $image->decline_reason = null;
        }
        if ($request->filled('approval_state')) {
            $image->approval_state = (string) $request->string('approval_state');
        }
        if ($request->exists('decline_reason')) {
            $image->decline_reason = $request->input('decline_reason');
        }
        if ($request->filled('crop')) {
            $image->crop = $request->input('crop');
        }
        $image->save();

        return response()->json($image->refresh());
    }

    public function approveImage(int $profileId)
    {
        $this->authorizeModeration();

        $image = ProfileImage::query()->where('profile_id', $profileId)->latest()->firstOrFail();
        $image->update([
            'approval_state' => 'approved',
            'decline_reason' => null,
        ]);

        return response()->json($image->refresh());
    }

    public function declineImage(Request $request, int $profileId)
    {
        $this->authorizeModeration();

        $validated = $request->validate([
            'decline_reason' => ['nullable', 'string'],
        ]);
        $image = ProfileImage::query()->where('profile_id', $profileId)->latest()->firstOrFail();
        $image->update([
            'approval_state' => 'declined',
            'decline_reason' => $validated['decline_reason'] ?? null,
        ]);

        return response()->json($image->refresh());
    }

    public function deleteImage(int $profileId)
    {
        Gate::authorize('update', $this->findProfile($profileId));

        $image = ProfileImage::query()->where('profile_id', $profileId)->latest()->firstOrFail();
        if ($image->path) {
            Storage::disk('local')->delete($image->path);
        }
        $image->delete();

        return response()->json(true);
    }

    public function getImage(int $profileId, ?string $size = null)
    {
        Gate::authorize('view', $this->findProfile($profileId));

        $image = ProfileImage::query()->where('profile_id', $profileId)->latest()->firstOrFail();

        return $this->imageProcessor->stream($image, $size);
    }

    public function crop(Request $request, int $profileId)
    {
        Gate::authorize('update', $this->findProfile($profileId));

        $image = ProfileImage::query()->where('profile_id', $profileId)->latest()->firstOrFail();
        $crop = $request->validate(['crop' => ['required', 'array']])['crop'];
        $this->imageProcessor->validateCrop($crop);
        $image->update(['crop' => $crop]);

        return response()->json($image->refresh());
    }

    public function indexImages(?string $approval_state = null)
    {
        $this->authorizeModeration();

        $query = ProfileImage::query();
        if ($approval_state !== null) {
            $query->where('approval_state', $approval_state);
        }

        return response()->json($query->latest()->get());
    }

    public function liveness()
    {
        return response('ok', 200);
    }

    private function authorizeModeration(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->hasAccessAttribute('profile:image:manager')
            || $user->hasAccessAttribute('event:owner') || $user->hasAccessAttribute('event:manager')), 403);
    }

    private function findProfile(int|string $id): UserProfile
    {
        if (is_numeric($id)) {
            return UserProfile::query()->findOrFail((int) $id);
        }

        return UserProfile::query()->where('user_id', $id)->firstOrFail();
    }
}
