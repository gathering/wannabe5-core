<?php

namespace App\Domain\Communication\Listeners;

use App\Domain\Communication\Jobs\HandleCommunicationDispatch;
use App\Domain\Shared\Events\BackendRequestForwarded;

class DispatchCommunicationWorkflowSideEffects
{
    public function handle(BackendRequestForwarded $event): void
    {
        if ($event->service !== 'communication' || strtoupper($event->method) !== 'POST') {
            return;
        }

        if ($event->statusCode < 200 || $event->statusCode >= 300) {
            return;
        }

        if (! in_array($event->path, ['messages/batch-send', 'messages'], true)) {
            return;
        }

        HandleCommunicationDispatch::dispatch($event->path, $event->query);
    }
}
