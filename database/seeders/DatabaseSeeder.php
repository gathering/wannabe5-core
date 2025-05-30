<?php

namespace Database\Seeders;

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
        // Create the development user
        $dev_user = User::firstOrCreate([
            'id' => 'eaf9efc2-adbb-4b27-b5a9-f6c60197ab56',
            'username' => 'testbruker',
        ]);
        // Create development token
        $dev_token = AccessToken::firstOrCreate([
            'name' => 'development',
            'user_id' => $dev_user->id,
            'token' => 'testbruker',
        ]);
    }
}
