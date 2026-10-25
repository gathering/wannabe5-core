<?php

use App\Models\Task;
use App\Models\TaskRecipient;
use App\Models\User;

test('tasking service supports event user and crew task workflows', function () {
    $user = User::factory()->create();

    $task = Task::create([
        'event_id' => 1,
        'name' => 'Safety briefing',
        'type' => 'forms',
        'enabled' => true,
        'short_description' => 'Read and confirm',
    ]);
    TaskRecipient::create([
        'task_id' => $task->id,
        'recipient_type' => 'user',
        'recipient_id' => $user->id,
        'mandatory' => true,
        'approval_needed' => true,
    ]);
    TaskRecipient::create([
        'task_id' => $task->id,
        'recipient_type' => 'crew',
        'recipient_id' => '10',
        'mandatory' => false,
        'approval_needed' => true,
    ]);

    $this->asUser($user)->getJson('/api/tasking/events/1')
        ->assertOk()
        ->assertJsonCount(1, 'data');
    $this->asUser($user)->getJson("/api/tasking/events/1/user/{$user->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.required', true)
        ->assertJsonPath('data.0.completed', false);

    // Layout gate: ?required=true returns only incomplete required tasks.
    $this->asUser($user)->getJson("/api/tasking/events/1/user/{$user->id}?required=true")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.required', true)
        ->assertJsonPath('data.0.completed', false);

    $this->asUser($user)->getJson("/api/tasking/events/1/user/{$user->id}/task/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $task->id)
        ->assertJsonPath('data.required', true);

    $response = $this->asUser($user)->postJson("/api/tasking/events/1/user/{$user->id}/task/{$task->id}", [
        'value' => ['ack' => true],
        'completed' => true,
    ])->assertStatus(201);
    expect($response->json('data.approved'))->toBeFalse();

    // After completion, required gate must be empty (no forced /tasks redirect).
    $this->asUser($user)->getJson("/api/tasking/events/1/user/{$user->id}?required=true")
        ->assertOk()
        ->assertJsonCount(0, 'data');
    $this->asUser($user)->getJson("/api/tasking/events/1/user/{$user->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.completed', true);

    $this->asUser($user)->postJson("/api/tasking/events/1/user/{$user->id}/task/{$task->id}/approve")
        ->assertOk()
        ->assertJsonPath('data.approved', true);

    $this->asUser($user)->getJson("/api/tasking/events/1/task/{$task->id}/responses")
        ->assertOk()
        ->assertJsonCount(1, 'data');
    $this->asUser($user)->getJson('/api/tasking/events/1/crew/10')
        ->assertOk()
        ->assertJsonCount(1, 'data');
    $this->asUser($user)->get('/api/tasking/liveness')->assertOk()->assertSee('ok');
});
