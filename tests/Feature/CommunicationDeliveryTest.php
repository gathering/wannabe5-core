<?php

use App\Domain\Communication\Jobs\PrepareAndSendMessage;
use App\Models\CommunicationMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

test('prepare and send message delivers mail through configured mailer', function () {
    Mail::fake();

    $message = CommunicationMessage::create([
        'channel' => 'mail',
        'notification_name' => 'welcome-mail',
        'recipient' => ['mail' => 'crew@example.com'],
        'data' => ['subject' => 'Hello', 'content' => 'Welcome aboard'],
        'source' => ['service' => 'test'],
        'state' => 'raw',
        'tries' => 0,
    ]);

    (new PrepareAndSendMessage($message->id))->handle(app(\App\Domain\Communication\Services\MessageDeliveryService::class));

    Mail::assertSent(\App\Mail\CommunicationChannelMail::class);
    expect($message->refresh()->state)->toBe('sent');
    expect($message->sent)->not->toBeNull();
});

test('prepare and send message logs sms delivery when provider is log', function () {
    Log::spy();
    config()->set('communication.sms_provider', 'log');

    $message = CommunicationMessage::create([
        'channel' => 'sms',
        'notification_name' => 'welcome-sms',
        'recipient' => ['phone' => '+4712345678'],
        'data' => ['content' => 'Ping'],
        'source' => ['service' => 'test'],
        'state' => 'raw',
        'tries' => 0,
    ]);

    (new PrepareAndSendMessage($message->id))->handle(app(\App\Domain\Communication\Services\MessageDeliveryService::class));

    expect($message->refresh()->state)->toBe('sent');
    Log::shouldHaveReceived('info')->withArgs(fn ($msg) => $msg === 'SMS delivery logged');
});
