<?php

use App\Models\User;

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

// Get profile with data filled
test('get profile', function () use ($profile_obj1, $profile_json1) {
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
