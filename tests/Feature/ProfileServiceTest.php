<?php

use App\Models\ProfileImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('profile service supports search create images and update-from-auth', function () {
    $user = User::factory()->create();
    $profileId = $user->userProfile->id;

    $this->asUser($user)->getJson('/api/profileservice/search?nickname=')->assertOk();
    $this->asUser($user)->getJson("/api/profileservice/profile/{$profileId}")->assertOk();
    $this->asUser($user)->getJson("/api/profileservice/profile/{$profileId}/crew-history")->assertOk();

    $image = $this->asUser($user)->post('/api/profileservice/profile/'.$profileId.'/profile-image', [
        'image' => UploadedFile::fake()->image('avatar.jpg'),
    ])->assertStatus(201);
    expect($image->json('approval_state'))->toBe('new');

    $this->asUser($user)->getJson("/api/profileservice/profile/{$profileId}/profile-image")->assertOk();
    $this->asUser($user)->getJson('/api/profileservice/image/new')->assertOk()->assertJsonCount(1);
    $this->asUser($user)->postJson("/api/profileservice/image/{$profileId}/crop", [
        'crop' => ['x' => 0.1, 'y' => 0.1, 'width' => 0.8, 'height' => 0.8],
    ])->assertOk();

    $defaultImage = $this->asUser($user)->get("/api/profileservice/profile/{$profileId}/profile-image/image");
    $defaultImage->assertOk();
    expect($defaultImage->headers->get('content-type'))->toStartWith('image/');
    expect(strlen($defaultImage->streamedContent()))->toBeGreaterThan(100);

    $thumb = $this->asUser($user)->get("/api/profile/profile/{$profileId}/profile-image/image/thumb");
    $thumb->assertOk();
    expect($thumb->headers->get('content-type'))->toStartWith('image/');
    expect(strlen($thumb->streamedContent()))->toBeGreaterThan(100);

    $original = $this->asUser($user)->get("/api/profile/profile/{$profileId}/profile-image/image/orginal");
    $original->assertOk();
    expect($original->headers->get('content-type'))->toStartWith('image/');
    expect(strlen($original->streamedContent()))->toBeGreaterThan(100);

    $this->asUser($user)->get("/api/profile/profile/{$profileId}/profile-image/image/huge")
        ->assertNotFound()
        ->assertJson(['error' => 'Size not supported']);

    $this->asUser($user)->postJson("/api/profileservice/profile/{$profileId}/profile-image/approve")
        ->assertOk()
        ->assertJsonPath('approval_state', 'approved');

    $this->asUser($user)->post('/api/profileservice/profile/'.$profileId.'/profile-image', [
        'image' => UploadedFile::fake()->image('avatar2.jpg'),
    ])->assertStatus(403);

    $this->asUser($user)->postJson("/api/profileservice/profile/{$profileId}/profile-image/decline", [
        'decline_reason' => 'blurry',
    ])->assertOk()->assertJsonPath('approval_state', 'declined');

    expect(ProfileImage::query()->where('profile_id', $profileId)->value('decline_reason'))->toBe('blurry');

    $this->asUser($user)->getJson('/api/profileservice/updatefromauth')
        ->assertOk()
        ->assertJsonPath('status', 'synced');
    $this->asUser($user)->get('/api/profileservice/liveness')->assertOk()->assertSee('ok');

    // Frontend-compatible alias under /api/profile (wannabe5-frontend service: "profile").
    $this->asUser($user)->getJson("/api/profile/profile/{$profileId}")->assertOk();
    $this->asUser($user)->getJson('/api/profile/search?nickname=')->assertOk();
});
