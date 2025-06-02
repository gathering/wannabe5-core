<?php

namespace App\Console\Commands;

use App\Models\AccessToken;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

class ListAccessTokens extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-access-tokens {userid : User UUID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all access tokens for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->table(
            ['ID', 'Name', 'Token', 'Last Used', 'Expires'],
            AccessToken::where('user_id', $this->argument('userid'))->get()->map(function ($token) {
                return [$token->id, $token->name, $token->token, $token->last_used_at ?: 'never', $token->expires_at ?: 'never'];
            })
        );
    }
}
