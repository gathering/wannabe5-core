<?php

namespace App\Domain\Communication\Jobs;

use App\Models\CommunicationMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class HandleCommunicationDispatch implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $path,
        public array $query = [],
    ) {}

    public function handle(): void
    {
        $pending = CommunicationMessage::query()
            ->whereIn('state', ['raw', 'ready'])
            ->orderBy('id')
            ->limit(50)
            ->get();

        foreach ($pending as $message) {
            PrepareAndSendMessage::dispatch($message->id);
        }

        Log::info('Communication dispatch processed', [
            'path' => $this->path,
            'query' => $this->query,
            'queued' => $pending->count(),
        ]);
    }
}
