<?php

use App\Domain\Communication\Jobs\PrepareAndSendMessage;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('communication service supports templates notifications messages and lists', function () {
    Queue::fake();
    $user = User::factory()->create();
    foreach (['com:admin', 'list:owner'] as $key) {
        \App\Models\AccessAttribute::create(['source' => 'communication', 'user_id' => $user->id, 'key' => $key, 'value' => ['*']]);
    }

    $template = $this->asUser($user)->postJson('/api/communication/templates', [
        'name' => 'welcome',
        'title' => 'Welcome',
        'content' => 'Hello {{name}}',
        'schema' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]],
    ])->assertStatus(201);
    $templateId = $template->json('id');

    $notification = $this->asUser($user)->postJson('/api/communication/notifications', [
        'name' => 'welcome-mail',
        'mail_template_id' => $templateId,
    ])->assertStatus(201);
    $notificationId = $notification->json('id');

    $message = $this->asUser($user)->postJson('/api/communication/messages', [
        'channel' => 'mail',
        'notification_name' => 'welcome-mail',
        'recipient' => ['mail' => 'user@example.com'],
        'data' => ['content' => 'Hello world', 'schema' => ['type' => 'object']],
        'source' => ['service' => 'core'],
    ])->assertStatus(201);
    $messageId = $message->json('id');

    $this->asUser($user)->getJson("/api/communication/messages/{$messageId}")->assertOk();
    $this->asUser($user)->get("/api/communication/messages/{$messageId}/preview")->assertOk()->assertSee('Hello world');
    $this->asUser($user)->getJson("/api/communication/messages/{$messageId}/schema")->assertOk();
    $this->asUser($user)->getJson("/api/communication/messages/{$messageId}/retry")
        ->assertOk()
        ->assertJsonPath('state', 'raw');

    $this->asUser($user)->postJson('/api/communication/messages/batch-send', [
        'channel' => 'mail',
        'notification_name' => 'welcome-mail',
        'recipients' => [
            ['id' => 'u1', 'type' => 'user'],
            ['id' => 'u2', 'type' => 'user'],
        ],
        'data' => ['content' => 'batch'],
    ])->assertStatus(201)->assertJsonPath('queued', 2);

    Queue::assertPushed(PrepareAndSendMessage::class);

    $list = $this->asUser($user)->postJson('/api/communication/lists', [
        'identifier' => 'crew-chiefs',
        'type' => 'mail',
        'summary' => 'Crew chiefs',
        'event_id' => 1,
    ])->assertStatus(201);
    $listId = $list->json('id');

    $owner = $this->asUser($user)->postJson('/api/communication/list-owners', [
        'list_id' => $listId,
        'owner_id' => $user->id,
        'owner_type' => 'user',
    ])->assertStatus(201);
    $recipient = $this->asUser($user)->postJson('/api/communication/list-recipients', [
        'list_id' => $listId,
        'recipient_id' => $user->id,
        'recipient_type' => 'user',
    ])->assertStatus(201);

    $this->asUser($user)->getJson("/api/communication/lists/{$listId}/owners")->assertOk()->assertJsonCount(1);
    $this->asUser($user)->getJson("/api/communication/lists/{$listId}/recipients")->assertOk()->assertJsonCount(1);
    $this->asUser($user)->getJson("/api/communication/templates/{$templateId}/schema")->assertOk();
    $this->asUser($user)->get("/api/communication/templates/{$templateId}/preview")->assertOk();
    $this->asUser($user)->getJson("/api/communication/notifications/{$notificationId}/schema")->assertOk();
    $this->asUser($user)->get('/api/communication/liveness')->assertOk()->assertSee('ok');

    $this->asUser($user)->deleteJson('/api/communication/list-recipients/'.$recipient->json('id'))->assertOk();
    $this->asUser($user)->deleteJson('/api/communication/list-owners/'.$owner->json('id'))->assertOk();
    $this->asUser($user)->deleteJson("/api/communication/lists/{$listId}")->assertOk();
    $this->asUser($user)->deleteJson("/api/communication/messages/{$messageId}")->assertOk();
    $this->asUser($user)->deleteJson("/api/communication/notifications/{$notificationId}")->assertOk();
    $this->asUser($user)->deleteJson("/api/communication/templates/{$templateId}")->assertOk();
});
