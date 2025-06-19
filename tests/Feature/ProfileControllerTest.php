<?php

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Testing\Fluent\AssertableJson;

$profile_obj1 = [
    'nickname' => 'Harald69',
    'gender' => 'male',
    'birthdate' => '1937-02-21',
    'phone' => '+4740612345',
    'streetaddress' => 'Slottsplassen 1',
    'postcode' => '0010',
    'town' => 'OSLO',
    'countrycode' => 'NO',
];

$profile_json1 = [
    'nickname' => 'Harald69',
    'gender' => 'male',
    'birthdate' => '1937-02-21',
    'phone' => '+4740612345',
    'address' => [
        'streetaddress' => 'Slottsplassen 1',
        'postcode' => '0010',
        'town' => 'OSLO',
        'countrycode' => 'NO',
    ],
];

$profile_json2 = [
    'nickname' => '_Carl42',
    'gender' => 'female',
    'birthdate' => '2000-04-15',
    'phone' => '+4740612346',
    'address' => [
        'streetaddress' => 'Kungliga slottet',
        'postcode' => '107 70',
        'town' => 'Stockholm',
        'countrycode' => 'SE',
    ],
];

$invalid_profile_json1 = [
    'birthdate' => '1800-04-15',
    'phone' => '+40612346',
    'address' => [
        'streetaddress' => 'Kungliga slottet',
        'postcode' => '010',
        'town' => 'Stockholm',
        'countrycode' => 'NO',
    ],
];

// Get profile with no data
test('get default profile', function () {
    $user = User::factory()->create();
    $response = $this->asUser($user)->getJson("/api/profile/{$user->userProfile->id}");

    $response->assertStatus(200);
});

test('get profile by user id', function () use ($profile_obj1, $profile_json1) {
    $user = User::factory()->create();
    $user->userProfile->update($profile_obj1);
    $response = $this->asUser($user)->getJson("/api/profile/{$user->userProfile->user_id}");

    $response->assertStatus(200);
    $response->assertJson(['data' => $profile_json1]);
});

test('get profile by profile id', function () use ($profile_obj1, $profile_json1) {
    $user = User::factory()->create();
    $user->userProfile->update($profile_obj1);
    $response = $this->asUser($user)->getJson("/api/profile/{$user->userProfile->id}");

    $response->assertStatus(200);
    $response->assertJson(['data' => $profile_json1]);
});

// Update profile, first from default state, then again with new data
test('update profile', function () use ($profile_json1, $profile_json2) {
    $user = User::factory()->create();

    $response = $this->asUser($user)->putJson("/api/profile/{$user->userProfile->id}", $profile_json1);
    $response->assertStatus(200)->assertValid();
    $response->assertJson(['data' => $profile_json1]);
    expect($user->refresh()->userProfile->nickname)->toBeString()->toBe($profile_json1['nickname']);

    $response = $this->asUser($user)->putJson("/api/profile/{$user->userProfile->id}", $profile_json2);
    $response->assertStatus(200)->assertValid();
    $response->assertJson(['data' => $profile_json2]);
    expect($user->refresh()->userProfile->nickname)->toBeString()->toBe($profile_json2['nickname']);
});

test('update profile with invalid data should fail validation', function () use ($invalid_profile_json1) {
    $user = User::factory()->create();

    $response = $this->asUser($user)->putJson("/api/profile/{$user->userProfile->id}", $invalid_profile_json1);
    $response->assertStatus(422);
    $response->assertInvalid(['nickname', 'birthdate', 'gender', 'phone', 'address.postcode']);

    $user->userProfile->crewHistory;
});

test('get profile by filter', function () use ($profile_obj1) {
    $this->seed(UserSeeder::class);

    $user = User::factory()->create();
    $user->userProfile->update($profile_obj1);

    $response = $this->asUser($user)->getJson("/api/profile?filter[id]={$user->userProfile->id}");
    $response->assertStatus(200);
    $response->assertJson(fn (AssertableJson $json) => $json->has('data', 1));

    $response = $this->asUser($user)->getJson("/api/profile?filter[user_id]={$user->id}");
    $response->assertStatus(200);
    $response->assertJson(fn (AssertableJson $json) => $json->has('data', 1));

    $response = $this->asUser($user)->getJson("/api/profile?filter[email]={$user->username}");
    $response->assertStatus(200);
    $response->assertJson(fn (AssertableJson $json) => $json->has('data', 1));

    $phone = urlencode($profile_obj1['phone']);
    $response = $this->asUser($user)->getJson("/api/profile?filter[phone]={$phone}");
    $response->assertStatus(200);
    $response->assertJson(fn (AssertableJson $json) => $json->has('data', 1));
});

test('invalid filters should fail', function () use ($profile_obj1) {
    $this->seed(UserSeeder::class);

    $user = User::factory()->create();
    $user->userProfile->update($profile_obj1);

    // No filter
    $response = $this->asUser($user)->getJson('/api/profile');
    $response->assertStatus(422);
    $response->assertInvalid(['filter.id', 'filter.user_id', 'filter.email', 'filter.phone']);

    // Incomplete email
    $response = $this->asUser($user)->getJson('/api/profile?filter[email]=example.com');
    $response->assertStatus(422);
    $response->assertInvalid(['filter.email']);

    // Random ID
    $response = $this->asUser($user)->getJson('/api/profile?filter[id]=1337');
    $response->assertStatus(404);
});

// Until we have better access control all other then self should fail
test('get profile for user should give 403', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $response = $this->asUser($user1)->getJson("/api/profile/{$user2->userProfile->id}");

    $response->assertStatus(403);
});
