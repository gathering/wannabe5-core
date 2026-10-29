<?php

namespace App\Domain\Application\Jobs;

use App\Domain\Communication\Jobs\PrepareAndSendMessage;
use App\Models\ApplicationRecord;
use App\Models\CommunicationMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class HandleApplicationStatusTransition implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $path,
        public array $query = [],
    ) {}

    public function handle(): void
    {
        if (! preg_match('/^(\d+)\/(received|reviewable|approved|waiting_list|declined)$/', $this->path, $matches)) {
            return;
        }

        $applicationId = (int) $matches[1];
        $status = $matches[2];
        $application = ApplicationRecord::query()->find($applicationId);
        if ($application === null) {
            return;
        }

        $message = CommunicationMessage::create([
            'channel' => 'mail',
            'notification_name' => 'application-status-'.$status,
            'recipient' => ['user_id' => $application->user_id],
            'data' => [
                'application_id' => $application->id,
                'status' => $status,
                'message' => $application->message,
            ],
            'source' => [
                'service' => 'application',
                'event' => 'status_transition',
            ],
            'state' => 'raw',
            'tries' => 0,
        ]);

        PrepareAndSendMessage::dispatch($message->id);

        Log::info('Application status transition side effects dispatched', [
            'path' => $this->path,
            'application_id' => $application->id,
            'status' => $status,
            'communication_message_id' => $message->id,
        ]);
    }
}
