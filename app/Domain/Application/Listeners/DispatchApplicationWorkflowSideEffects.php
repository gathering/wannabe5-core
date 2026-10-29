<?php

namespace App\Domain\Application\Listeners;

use App\Domain\Application\Jobs\HandleApplicationStatusTransition;
use App\Domain\Shared\Events\BackendRequestForwarded;

class DispatchApplicationWorkflowSideEffects
{
    public function handle(BackendRequestForwarded $event): void
    {
        if ($event->service !== 'application' || strtoupper($event->method) !== 'PUT') {
            return;
        }

        if ($event->statusCode < 200 || $event->statusCode >= 300) {
            return;
        }

        if (! preg_match('/^\d+\/(received|reviewable|approved|waiting_list|declined)$/', $event->path)) {
            return;
        }

        HandleApplicationStatusTransition::dispatch($event->path, $event->query);
    }
}
