<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListServiceUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-service-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all users of type "service"';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->table(
            ['ID', 'Username', 'Tokens'],
            User::all()->map(function ($user) {
                return [$user->id, $user->username, $user->accessTokens->count()];
            })
        );
    }
}
