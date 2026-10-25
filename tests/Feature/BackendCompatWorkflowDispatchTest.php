<?php

use App\Domain\Application\Jobs\HandleApplicationStatusTransition;
use App\Domain\Communication\Jobs\HandleCommunicationDispatch;
use App\Domain\Profile\Jobs\HandleProfileSyncFromAuth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('application status transition dispatches async workflow job', function () {
    config()->set('backend_compat.services.application', 'https://backend.local/api/application');
    Queue::fake();

    Http::fake([
        'https://backend.local/api/application/123/approved' => Http::response(['status' => 1], 200),
    ]);

    $this->putJson('/api/ms/application/123/approved')->assertOk();

    Queue::assertPushed(HandleApplicationStatusTransition::class);
});

test('application transition does not dispatch workflow job when upstream rejects transition', function () {
    config()->set('backend_compat.services.application', 'https://backend.local/api/application');
    Queue::fake();

    Http::fake([
        'https://backend.local/api/application/123/approved' => Http::response(['status' => 0], 422),
    ]);

    $this->putJson('/api/ms/application/123/approved')->assertStatus(422);

    Queue::assertNotPushed(HandleApplicationStatusTransition::class);
});

test('auth flow endpoints dispatch profile sync job', function () {
    config()->set('backend_compat.services.auth', 'https://backend.local/api/auth');
    Queue::fake();

    Http::fake([
        'https://backend.local/api/auth/refresh-session' => Http::response('renewed', 200),
    ]);

    $this->post('/api/ms/auth/refresh-session')->assertOk();

    Queue::assertPushed(HandleProfileSyncFromAuth::class);
});

test('communication batch send dispatches async communication job', function () {
    config()->set('backend_compat.services.communication', 'https://backend.local/api/communication');
    Queue::fake();

    Http::fake([
        'https://backend.local/api/communication/messages/batch-send' => Http::response(['queued' => 2], 200),
    ]);

    $this->postJson('/api/ms/communication/messages/batch-send', ['messages' => []])->assertOk();

    Queue::assertPushed(HandleCommunicationDispatch::class);
});
