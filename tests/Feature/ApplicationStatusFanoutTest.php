<?php

use App\Domain\Application\Jobs\HandleApplicationStatusTransition;
use App\Domain\Communication\Jobs\PrepareAndSendMessage;
use App\Models\CommunicationMessage;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('native application status transition dispatches and fans out a communication message', function () {
    Queue::fake();

    $user = User::factory()->create();
    \App\Models\AccessAttribute::create(['source' => 'test', 'user_id' => $user->id, 'key' => 'crew:applications:manager', 'value' => ['10']]);
    $applicationId = $this->asUser($user)->postJson('/api/application', [
        'user_id' => $user->id,
        'event_id' => 1,
        'crew_id' => 10,
    ])->assertStatus(201)->json('id');

    $this->asUser($user)->putJson("/api/application/{$applicationId}/approved", [
        'message' => 'Welcome',
    ])->assertOk()->assertJsonPath('status', 'approved');

    Queue::assertPushed(HandleApplicationStatusTransition::class, function (HandleApplicationStatusTransition $job) use ($applicationId) {
        expect($job->path)->toBe("{$applicationId}/approved");
        $job->handle();

        return true;
    });

    $message = CommunicationMessage::query()
        ->where('notification_name', 'application-status-approved')
        ->latest('id')
        ->first();

    expect($message)->not->toBeNull();
    expect($message->data['application_id'] ?? null)->toBe($applicationId);
    Queue::assertPushed(PrepareAndSendMessage::class);
});
