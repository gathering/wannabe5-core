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
        $precommitConfig = '
            #!/bin/bash
            ./vendor/bin/pint
            php artisan scramble:export';
        if (file_put_contents('.git/hooks/pre-commit', $precommitConfig) !== false) {
            echo 'Pre-commit config written successfully.'.PHP_EOL;
        } else {
            echo 'Failed to write pre-commit config.'.PHP_EOL;
        }
    }
}
