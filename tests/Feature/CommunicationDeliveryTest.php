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

test('forced log delivery selects the log mailer even when SMTP is configured', function () {
    config()->set('mail.default', 'smtp');
    config()->set('communication.force_log_delivery', true);
    Mail::shouldReceive('mailer')->once()->with('log')->andReturnSelf();
    Mail::shouldReceive('to')->once()->with('crew@example.com')->andReturnSelf();
    Mail::shouldReceive('send')->once()->with(Mockery::type(\App\Mail\CommunicationChannelMail::class));

    $message = new CommunicationMessage([
        'channel' => 'mail', 'recipient' => ['mail' => 'crew@example.com'],
        'data' => ['content' => 'Test'],
    ]);
    app(\App\Domain\Communication\Services\MessageDeliveryService::class)->deliver($message);
});

test('mail delivery resolves workflow user IDs and skips already sent messages', function () {
    Mail::fake();
    $user = \App\Models\User::factory()->create();
    $message = CommunicationMessage::create([
        'channel' => 'mail', 'recipient' => ['user_id' => $user->id],
        'data' => ['content' => 'Status changed'], 'state' => 'raw', 'tries' => 0,
    ]);
    $job = new PrepareAndSendMessage($message->id);
    $service = app(\App\Domain\Communication\Services\MessageDeliveryService::class);
    $job->handle($service);
    $job->handle($service);
    Mail::assertSent(\App\Mail\CommunicationChannelMail::class, fn ($mail) => $mail->hasTo($user->userProfile->email));
    Mail::assertSentCount(1);
    expect($message->fresh()->tries)->toBe(1);
});

test('missing mail address records a retryable failure instead of marking the message sent', function () {
    $message = CommunicationMessage::create(['channel' => 'mail', 'recipient' => [], 'state' => 'raw', 'tries' => 0]);
    expect(fn () => (new PrepareAndSendMessage($message->id))->handle(app(\App\Domain\Communication\Services\MessageDeliveryService::class)))
        ->toThrow(RuntimeException::class);
    expect($message->fresh()->state)->toBe('failed');
    expect($message->fresh()->tries)->toBe(1);
    expect($message->fresh()->sent)->toBeNull();
});
