<?php

namespace App\Domain\Profile\Listeners;

use App\Domain\Profile\Jobs\HandleProfileSyncFromAuth;
use App\Domain\Shared\Events\BackendRequestForwarded;

class DispatchProfileSyncFromAuth
{
    public function handle(BackendRequestForwarded $event): void
    {
        if ($event->service !== 'auth') {
            return;
        }

        if ($event->statusCode < 200 || $event->statusCode >= 300) {
            return;
        }

        if (! in_array($event->path, ['after-login', 'refresh-session'], true)) {
            return;
        }

        HandleProfileSyncFromAuth::dispatch($event->path, $event->query);
    }
}
