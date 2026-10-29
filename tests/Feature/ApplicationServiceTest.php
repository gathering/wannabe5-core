<?php

use App\Domain\Application\Jobs\HandleApplicationStatusTransition;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('application service supports application question answer and comment flows', function () {
    Queue::fake();
    $user = User::factory()->create();
    \App\Models\AccessAttribute::create(['source' => 'application', 'user_id' => $user->id, 'key' => 'crew:applications:manager', 'value' => ['10']]);

    \App\Models\AccessAttribute::create(['source' => 'test', 'user_id' => $user->id, 'key' => 'event:owner', 'value' => ['1']]);
    $createApplication = $this->asUser($user)->postJson('/api/application', [
        'user_id' => $user->id,
        'event_id' => 1,
        'crew_id' => 10,
    ]);
    $createApplication->assertStatus(201);
    $applicationId = $createApplication->json('id');

    $this->asUser($user)->getJson('/api/application/10/applications?event_id=1')
        ->assertOk()
        ->assertJsonCount(1);
    $this->asUser($user)->getJson("/api/application/application/{$applicationId}")
        ->assertOk()
        ->assertJsonPath('id', $applicationId);

    $question = $this->asUser($user)->postJson('/api/application/questions', [
        'event_id' => 1,
        'type' => 'text',
        'title' => 'Why do you want to join?',
        'required' => true,
        'options' => [],
    ])->assertStatus(201);
    $questionId = $question->json('id');

    $this->asUser($user)->postJson('/api/application/answers', [
        'application_id' => $applicationId,
        'question_id' => $questionId,
        'content' => 'I can help.',
    ])->assertStatus(201);

    $answerId = $this->asUser($user)->getJson("/api/application/answer/{$questionId}")
        ->assertOk()
        ->json('0.id');
    $this->asUser($user)->putJson("/api/application/answer/{$answerId}", [
        'application_id' => $applicationId,
        'question_id' => $questionId,
        'content' => 'Updated answer.',
    ])->assertOk()->assertJsonPath('content', 'Updated answer.');

    $comment = $this->asUser($user)->postJson("/api/application/{$applicationId}/comments", [
        'comment' => 'Looks good',
    ])->assertStatus(201);
    $commentId = $comment->json('id');

    $this->asUser($user)->putJson("/api/application/{$applicationId}/comments/{$commentId}", [
        'comment' => 'Looks really good',
    ])->assertOk()->assertJsonPath('comment', 'Looks really good');
    $this->asUser($user)->getJson("/api/application/{$applicationId}/comments")
        ->assertOk()
        ->assertJsonCount(1);

    $this->asUser($user)->putJson("/api/application/{$applicationId}/approved", [
        'message' => 'Accepted',
    ])->assertOk()->assertJsonPath('status', 'approved');

    Queue::assertPushed(HandleApplicationStatusTransition::class);

    $this->asUser($user)->deleteJson("/api/application/{$applicationId}/comments/{$commentId}")->assertOk();
    $this->asUser($user)->deleteJson("/api/application/answer/{$answerId}")->assertOk();
    $this->asUser($user)->deleteJson("/api/application/questions/{$questionId}")->assertOk();
    $this->asUser($user)->deleteJson("/api/application/{$applicationId}")->assertOk();
});
