<?php

namespace App\Console\Commands;

use App\Models\AccessToken;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;

class CreateServiceUser extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-service-user {name : Name of service-user and token}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new user with type "service". Will also generate access-token';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // TODO Input validation

        $user = new User;
        $user->id = (string) Str::uuid();
        $user->username = $this->argument('name');
        $user->type = 'service';
        $user->save();

        $token = new AccessToken;
        $token->user_id = $user->id;
        $token->name = $this->argument('name');
        $token->save();

        $this->table(
            ['User ID', 'Token Name', 'Token', 'Last Used', 'Expires'],
            [[$user->id, $token->name, $token->token, $token->last_used_at ?: 'never', $token->expires_at ?: 'never']]
        );
    }
}
