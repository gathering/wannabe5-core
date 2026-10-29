<?php

use App\Models\Task;
use App\Models\TaskQuestion;
use App\Models\TaskRecipient;
use App\Models\TaskResponse;
use App\Models\User;

test('tasking service supports event user and crew task workflows', function () {
    $user = User::factory()->create();
    \App\Models\AccessAttribute::create(['source' => 'tasking', 'user_id' => $user->id, 'key' => 'event:owner', 'value' => ['1']]);

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

    // Frontend expects 200 even on first create (not 201).
    $response = $this->asUser($user)->postJson("/api/tasking/events/1/user/{$user->id}/task/{$task->id}", [
        'value' => ['ack' => true],
        'completed' => true,
    ])->assertOk();
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

test('task response accepts frontend questions payload and returns 200 on first save', function () {
    $user = User::factory()->create();

    $task = Task::create([
        'event_id' => 1,
        'name' => 'Showup',
        'type' => 'forms',
        'enabled' => true,
        'skippable' => true,
        'short_description' => 'Arrive',
    ]);
    TaskRecipient::create([
        'task_id' => $task->id,
        'recipient_type' => 'user',
        'recipient_id' => $user->id,
        'mandatory' => true,
        'approval_needed' => false,
    ]);
    $question = TaskQuestion::create([
        'task_id' => $task->id,
        'optional' => false,
        'hidden' => false,
        'name' => 'Arrival time',
        'description' => null,
        'type' => 'textfield',
        'options' => null,
    ]);

    $this->asUser($user)->postJson("/api/tasking/events/1/user/{$user->id}/task/{$task->id}", [
        'questions' => [
            (string) $question->id => 'Wednesday 09:00',
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.completed', true)
        ->assertJsonPath('data.skipped', false)
        ->assertJsonPath('data.value.'.$question->id, 'Wednesday 09:00');

    expect(TaskResponse::query()->where('task_id', $task->id)->where('user_id', $user->id)->count())->toBe(1);

    $this->asUser($user)->postJson("/api/tasking/events/1/user/{$user->id}/task/{$task->id}", [
        'questions' => null,
    ])
        ->assertOk()
        ->assertJsonPath('data.skipped', true)
        ->assertJsonPath('data.completed', true);
});
