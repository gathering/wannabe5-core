<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class precommit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:precommit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Writes current pre-commit config to .git/hooks/pre-commit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hook = <<<'HOOK'
#!/usr/bin/env bash
set -euo pipefail

./vendor/bin/pint

# Scramble needs DB schema introspection. Host .env uses DB_HOST=db (compose DNS),
# which does not resolve outside the container — that aborts commits with a wall of PDO noise.
if command -v docker >/dev/null 2>&1 && docker compose ps --status running --services 2>/dev/null | grep -qx devcontainer; then
  docker compose exec -T -e TELESCOPE_ENABLED=false devcontainer php artisan scramble:export
else
  DB_HOST=127.0.0.1 TELESCOPE_ENABLED=false php artisan scramble:export
fi
HOOK;

        if (file_put_contents('.git/hooks/pre-commit', $hook) !== false) {
            chmod('.git/hooks/pre-commit', 0755);
            $this->info('Pre-commit config written successfully.');
        } else {
            $this->error('Failed to write pre-commit config.');
        }
    }
}
