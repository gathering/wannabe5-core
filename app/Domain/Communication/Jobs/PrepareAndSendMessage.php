<?php

namespace App\Domain\Communication\Jobs;

use App\Domain\Communication\Services\MessageDeliveryService;
use App\Models\CommunicationMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PrepareAndSendMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(
        public int $messageId,
    ) {}

    public function handle(MessageDeliveryService $delivery): void
    {
        $failure = DB::transaction(fn () => $this->deliverLocked($delivery));
        if ($failure !== null) {
            throw $failure;
        }
    }

    private function deliverLocked(MessageDeliveryService $delivery): ?Throwable
    {
        $message = CommunicationMessage::query()->lockForUpdate()->find($this->messageId);
        if ($message === null) {
            return null;
        }

        if ($message->state === 'sent') {
            return null;
        }

        try {
            $message->update([
                'state' => 'ready',
                'last_error' => null,
            ]);

            $delivery->deliver($message);

            $message->update([
                'state' => 'sent',
                'sent' => now(),
                'tries' => $message->tries + 1,
            ]);

            Log::info('Communication message prepared and sent', [
                'message_id' => $message->id,
                'channel' => $message->channel,
            ]);
        } catch (Throwable $exception) {
            $message->update([
                'state' => 'failed',
                'last_error' => $exception->getMessage(),
                'tries' => $message->tries + 1,
            ]);

            return $exception;
        }

        return null;
    }
}
