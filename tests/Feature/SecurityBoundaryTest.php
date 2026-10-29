<?php

use App\Models\CommunicationMessage;
use App\Models\Page;
use App\Models\Task;
use App\Models\TaskRecipient;
use App\Models\TaskResponse;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('ordinary users cannot send messages or read communication data', function () {
    Queue::fake();
    $user = User::factory()->create();
    $message = CommunicationMessage::create(['channel' => 'mail', 'recipient' => ['mail' => 'private@example.test'], 'state' => 'raw', 'tries' => 0]);
    foreach (['messages', 'templates', 'notifications', 'lists', 'list-owners', 'list-recipients'] as $resource) {
        $this->asUser($user)->getJson('/api/communication/'.$resource)->assertForbidden();
    }
    $this->asUser($user)->getJson('/api/communication/messages/'.$message->id)->assertForbidden();
    $this->asUser($user)->postJson('/api/communication/messages', ['channel' => 'mail'])->assertForbidden();
    $this->asUser($user)->getJson('/api/communication/messages/'.$message->id.'/retry')->assertForbidden();
    Queue::assertNothingPushed();
});

test('task response ownership assignment and approval are enforced', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $task = Task::create(['event_id' => 1, 'name' => 'Private task', 'type' => 'forms', 'enabled' => true]);
    $base = '/api/tasking/events/1/user/'.$user->id.'/task/'.$task->id;
    $this->asUser($user)->postJson($base, ['completed' => true])->assertForbidden();
    TaskRecipient::create(['task_id' => $task->id, 'recipient_type' => 'user', 'recipient_id' => $user->id]);
    $this->asUser($user)->postJson($base, ['completed' => true])->assertOk();
    $this->asUser($other)->postJson($base, ['completed' => true])->assertForbidden();
    $this->asUser($user)->postJson($base.'/approve')->assertForbidden();
    $this->asUser($other)->getJson('/api/tasking/events/1/task/'.$task->id.'/responses')->assertForbidden();
    expect(TaskResponse::where('task_id', $task->id)->first()->approved)->toBeFalse();
});

test('private pages and historical versions cannot be accessed or modified by unrelated users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $page = Page::factory()->create(['author_id' => $owner->id, 'public' => false]);
    $this->asUser($other)->getJson('/api/page/'.$page->id)->assertForbidden();
    $this->asUser($other)->getJson('/api/page/'.$page->id.'/versions')->assertForbidden();
    $this->asUser($other)->deleteJson('/api/page/'.$page->id)->assertForbidden();
    $this->asUser($other)->postJson('/api/page', ['title' => 'Forged', 'content' => '{}', 'slug' => 'forged', 'author_id' => $owner->id])->assertForbidden();
    expect($page->fresh())->not->toBeNull();
});

test('crop allocation is bounded before image processing', function () {
    $processor = app(\App\Domain\Profile\Services\ProfileImageProcessor::class);
    $processor->validateCrop(['x' => 0.1, 'y' => 0.1, 'width' => 0.8, 'height' => 0.8]);
    expect(fn () => $processor->validateCrop(['width' => 1000000, 'height' => 1000000]))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
    expect(fn () => $processor->validateCrop(['x' => 0.5, 'width' => 0.8]))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('crew membership writes reject unrelated actors and foreign teams', function () {
    $manager = User::factory()->create();
    $other = User::factory()->create();
    $event = \App\Models\CrewEvent::create(['name' => 'Security', 'slug' => 'security', 'location' => 'Test', 'start_date' => '2026-01-01', 'end_date' => '2026-01-02', 'visible' => true, 'active' => true]);
    $crew = \App\Models\Crew::create(['event_id' => $event->id, 'name' => 'Managed']);
    $foreignCrew = \App\Models\Crew::create(['event_id' => $event->id, 'name' => 'Foreign']);
    $team = \App\Models\CrewTeam::create(['crew_id' => $foreignCrew->id, 'name' => 'Foreign team']);
    $role = \App\Models\CrewRole::create(['event_id' => $event->id, 'name' => 'member', 'title' => 'Member']);
    \App\Models\AccessAttribute::create(['source' => 'test', 'user_id' => $manager->id, 'key' => 'crew:manager', 'value' => [(string) $crew->id]]);
    $path = "/api/crew/events/{$event->id}/crews/{$crew->id}/users";
    $this->asUser($other)->postJson($path, ['user_id' => $other->id, 'role_id' => $role->id])->assertForbidden();
    $this->asUser($manager)->postJson($path, ['user_id' => $other->id, 'role_id' => $role->id, 'team_id' => $team->id])->assertUnprocessable();
    $this->asUser($manager)->postJson($path, ['user_id' => $other->id, 'role_id' => $role->id])->assertOk();
    $this->asUser($other)->deleteJson($path.'/'.$other->id)->assertForbidden();
});
