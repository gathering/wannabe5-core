<?php

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can be instantiated', function () {
    $user = new User;
    $user->id = 'a6a7c6a3-fa75-4791-b4db-f3a5e427f6f6';
    $user->username = 'test@example.com';
    $user->type = 'user';
    $user->save();

    $this->assertDatabaseHas('users', [
        'id' => 'a6a7c6a3-fa75-4791-b4db-f3a5e427f6f6',
    ]);
});

test('user factory creates new user', function () {
    $user = User::factory()->create();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
    ]);

    expect($user->id)->toBeUuid();
    expect($user->type)->toBeString()->toBe('user');
    expect($user->created_at)->not->toBeNull();
});

test('user factory creates new service user', function () {
    $user = User::factory()->create(['username' => 'svc-account', 'type' => 'service']);
    expect($user->type)->toBeString()->toBe('service');
});

test('user seed creates new users', function () {
    $this->seed(UserSeeder::class);
    expect(User::all())->toHaveCount(10);
});
