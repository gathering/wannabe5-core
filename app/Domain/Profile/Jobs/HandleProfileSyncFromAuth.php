<?php

namespace App\Domain\Profile\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class HandleProfileSyncFromAuth implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $path,
        public array $query = [],
    ) {}

    public function handle(): void
    {
        // Placeholder for auth->profile synchronization hooks.
        Log::info('Profile sync trigger forwarded from auth flow', [
            'path' => $this->path,
            'query' => $this->query,
        ]);
    }
}
