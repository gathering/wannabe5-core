<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        // Reuse the local admin user so pages do not spawn orphan factory users.
        Page::factory(10)->create([
            'author_id' => 'eaf9efc2-adbb-4b27-b5a9-f6c60197ab56',
        ]);
    }
}
