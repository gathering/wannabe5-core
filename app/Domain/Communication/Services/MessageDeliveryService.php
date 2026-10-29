<?php

namespace App\Domain\Communication\Services;

use App\Mail\CommunicationChannelMail;
use App\Models\CommunicationMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class MessageDeliveryService
{
    public function deliver(CommunicationMessage $message): void
    {
        match ($message->channel) {
            'mail' => $this->deliverMail($message),
            'sms' => $this->deliverSms($message),
            'slack' => $this->deliverSlack($message),
            default => throw new RuntimeException("Unsupported channel [{$message->channel}]"),
        };
    }

    private function deliverMail(CommunicationMessage $message): void
    {
        $recipient = $message->recipient ?? [];
        $to = $recipient['mail'] ?? $recipient['email'] ?? null;
        if ($to === null && isset($recipient['user_id'])) {
            $to = \App\Models\UserProfile::query()->where('user_id', $recipient['user_id'])->value('email');
        }
        if (! is_string($to) || $to === '') {
            throw new RuntimeException('Mail recipient address is missing.');
        }

        $driver = (bool) config('communication.force_log_delivery', false)
            ? 'log'
            : (string) config('mail.default', 'log');
        $subject = (string) ($message->data['subject'] ?? $message->notification_name ?? 'Wannabe5 message');
        $body = (string) ($message->data['content'] ?? json_encode($message->data ?? [], JSON_THROW_ON_ERROR));

        if (in_array($driver, ['array', 'log'], true) || (bool) config('communication.force_log_delivery', false)) {
            Mail::mailer($driver)->to($to)->send(new CommunicationChannelMail($subject, $body));

            return;
        }

        Mail::to($to)->send(new CommunicationChannelMail($subject, $body));
    }

    private function deliverSms(CommunicationMessage $message): void
    {
        $recipient = $message->recipient ?? [];
        $phone = $recipient['sms'] ?? $recipient['phone'] ?? null;
        if (! is_string($phone) || $phone === '') {
            throw new RuntimeException('SMS recipient phone is missing.');
        }

        $provider = (string) config('communication.sms_provider', 'log');
        $body = (string) ($message->data['content'] ?? '');

        if ($provider === 'log' || blank(config('communication.sms_endpoint'))) {
            Log::info('SMS delivery logged', [
                'message_id' => $message->id,
                'phone' => $phone,
                'body' => $body,
            ]);

            return;
        }

        // Endpoint-based provider hook for production SMS gateways.
        $response = \Illuminate\Support\Facades\Http::asJson()->post((string) config('communication.sms_endpoint'), [
            'to' => $phone,
            'body' => $body,
            'message_id' => $message->id,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('SMS provider rejected message: '.$response->body());
        }
    }

    private function deliverSlack(CommunicationMessage $message): void
    {
        $recipient = $message->recipient ?? [];
        $channel = $recipient['slack'] ?? $recipient['channel'] ?? null;
        if (! is_string($channel) || $channel === '') {
            throw new RuntimeException('Slack recipient channel is missing.');
        }

        $webhook = (string) config('communication.slack_webhook');
        $body = (string) ($message->data['content'] ?? '');

        if ($webhook === '') {
            Log::info('Slack delivery logged', [
                'message_id' => $message->id,
                'channel' => $channel,
                'body' => $body,
            ]);

            return;
        }

        $response = \Illuminate\Support\Facades\Http::asJson()->post($webhook, [
            'channel' => $channel,
            'text' => $body,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Slack webhook rejected message: '.$response->body());
        }
    }
}
