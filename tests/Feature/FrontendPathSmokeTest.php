<?php

use App\Models\AccessAttribute;
use App\Models\Task;
use App\Models\TaskRecipient;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

/**
 * Path-level smoke coverage for wannabe5-frontend doApiRequest shapes:
 * `${API_URL}/${service}/${route}` (API_URL typically ends with `/api`).
 */
test('frontend auth refresh-session path refreshes an authenticated session', function () {
    $user = User::factory()->create();

    $this->asUser($user)->get('/api/auth/refresh-session')->assertOk()->assertSee('valid');
    $this->asUser($user)->post('/api/auth/refresh-session')->assertOk()->assertSee('valid');
});

test('logged-out homepage can fetch public crew frontpage without auth', function () {
    $this->getJson('/api/crew/pages/frontpage')
        ->assertOk()
        ->assertJsonPath('type', 'public_frontpage')
        ->assertJsonPath('content.type', 'doc')
        ->assertJsonPath('content.content', []);
});

test('frontend profile service paths resolve under /api/profile', function () {
    $user = User::factory()->create();
    $profileId = $user->userProfile->id;

    $this->asUser($user)->getJson("/api/profile/profile/{$profileId}")->assertOk();
    $this->asUser($user)->getJson('/api/profile/search?nickname=')->assertOk();
    $this->asUser($user)->getJson('/api/profile/image/new')->assertOk();
    $this->asUser($user)->getJson('/api/profile/updatefromauth')->assertOk();
    $this->asUser($user)->get('/api/profile/liveness')->assertOk()->assertSee('ok');
});

test('frontend application crew and communication paths resolve under native prefixes', function () {
    Queue::fake();
    $user = User::factory()->create();
    AccessAttribute::query()->create([
        'source' => 'test',
        'user_id' => $user->id,
        'key' => 'event:owner',
        'value' => ['*'],
    ]);

    $applicationId = $this->asUser($user)->postJson('/api/application', [
        'user_id' => $user->id,
        'event_id' => 1,
        'crew_id' => 10,
    ])->assertStatus(201)->json('id');

    $this->asUser($user)->getJson("/api/application/application/{$applicationId}")->assertOk();
    $this->asUser($user)->getJson("/api/application/{$applicationId}/comments")->assertOk();
    $this->asUser($user)->getJson('/api/application/questions?crew_id=10')->assertOk();

    $eventId = $this->asUser($user)->postJson('/api/crew/events', [
        'name' => 'Frontend Smoke Event',
        'slug' => 'frontend-smoke',
        'location' => 'Test',
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-05',
        'visible' => true,
        'active' => true,
        'applications_open' => true,
    ])->assertStatus(201)->json('data.id');

    $this->asUser($user)->getJson("/api/crew/events/{$eventId}")
        ->assertOk()
        ->assertJsonPath('data.id', $eventId);

    $templateId = $this->asUser($user)->postJson('/api/communication/templates', [
        'name' => 'frontend-smoke',
        'title' => 'Smoke',
        'content' => 'Hello',
        'schema' => ['type' => 'object'],
    ])->assertStatus(201)->json('id');

    $this->asUser($user)->postJson('/api/communication/notifications', [
        'name' => 'frontend-smoke-mail',
        'mail_template_id' => $templateId,
    ])->assertStatus(201);

    $messageId = $this->asUser($user)->postJson('/api/communication/messages', [
        'channel' => 'mail',
        'notification_name' => 'frontend-smoke-mail',
        'recipient' => ['mail' => 'user@example.com'],
        'data' => ['content' => 'Hello world', 'schema' => ['type' => 'object']],
        'source' => ['service' => 'core'],
    ])->assertStatus(201)->json('id');

    $this->asUser($user)->getJson('/api/communication/messages?page=0&per_page=10')->assertOk();
    $this->asUser($user)->getJson("/api/communication/messages/{$messageId}")->assertOk();
});

test('frontend tasking paths resolve under /api/tasking', function () {
    $user = User::factory()->create();

    $task = Task::create([
        'event_id' => 1,
        'name' => 'Frontend smoke task',
        'type' => 'forms',
        'enabled' => true,
        'short_description' => 'Smoke',
    ]);
    TaskRecipient::create([
        'task_id' => $task->id,
        'recipient_type' => 'crew',
        'recipient_id' => '10',
        'mandatory' => false,
        'approval_needed' => false,
    ]);

    $this->asUser($user)->getJson('/api/tasking/events/1')->assertOk();
    $this->asUser($user)->getJson("/api/tasking/events/1/task/{$task->id}")->assertOk();
    $this->asUser($user)->getJson('/api/tasking/events/1/crew/10')->assertOk();
});

test('core UserProfile resource still resolves numeric and uuid ids', function () {
    $user = User::factory()->create();
    $profileId = $user->userProfile->id;

    $this->asUser($user)->getJson("/api/profile/{$profileId}")->assertOk();
    $this->asUser($user)->getJson("/api/profile/{$user->id}")->assertOk();
});
