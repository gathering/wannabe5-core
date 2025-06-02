<?php

namespace Database\Seeders;

use App\Enums\UserProfileGender;
use App\Models\AccessToken;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds for development
     */
    public function run(): void
    {
        // Do not run in production, we don't want test users with tokens in live systems
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        // Create the development user
        $dev_user = User::firstOrCreate([
            'id' => 'eaf9efc2-adbb-4b27-b5a9-f6c60197ab56',
            'username' => 'testbruker@wannabe.no',
            'type' => 'user',
        ]);

        // Update UserProfile for development user
        $dev_user->profile->update([
            'firstname' => 'Test',
            'lastname' => 'Brukersen',
            'nickname' => 'testbruker',
            'gender' => UserProfileGender::MALE,
            'birth' => '19920415',
            'phone' => fake()->e164PhoneNumber(),
        ]);

        // Create development token
        $dev_token = AccessToken::firstOrCreate([
            'name' => 'development',
            'user_id' => $dev_user->id,
            'token' => 'testbruker',
        ]);
    }
}
