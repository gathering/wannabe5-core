<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserCrewHistory;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()
            ->count(10)
            ->has(UserProfile::factory()->state(function (array $attributes, User $user) {
                return ['email' => $user->username];
            })->has(UserCrewHistory::factory()->count(5)))
            ->createQuietly(); // Do not create profiles when user is created, we do it
    }
}
