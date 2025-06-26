<?php

use App\Models\Page;
use App\Models\User;
use Database\Seeders\PageSeeder;

test('get empty index', function () {
    $user = User::factory()->create();

    $response = $this->asUser($user)->getJson('/api/page');
    $response->assertStatus(200);
});

test('get index with pages', function () {
    $user = User::factory()->create();

    // Create 10 pages
    $this->seed(PageSeeder::class);

    $response = $this->asUser($user)->getJson('/api/page');
    $response->assertStatus(200)->assertJsonCount(10, 'data');
});

test('get page', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create();

    $response = $this->asUser($user)->getJson("/api/page/{$page->id}");

    $response->assertStatus(200)->assertJsonStructure(['data' => ['id', 'title', 'slug', 'content', 'author_id']]);
});

test('create new page', function () {
    $user = User::factory()->create();

    $response = $this->asUser($user)->postJson('/api/page/', [
        'title' => 'test title',
        'content' => 'test content',
        'author_id' => $user->id,
        'slug' => 'test',
    ]);

    $response->assertCreated()->assertJsonStructure(['data' => ['id', 'title', 'slug', 'content', 'author_id']]);
    $response->assertJsonPath('data.author_id', $user->id);
});

test('update page', function () {
    $user1 = User::factory()->create();

    $response = $this->asUser($user1)->postJson('/api/page/', [
        'title' => 'test title',
        'content' => '{"test":"test2"}',
        'author_id' => $user1->id,
        'slug' => 'test',
    ]);

    $user2 = User::factory()->create();

    $response = $this->asUser($user2)->putJson('/api/page/1', [
        'title' => 'new title',
        'content' => '{"test":"new2"}',
        'author_id' => $user2->id,
        'slug' => 'newslug',
    ]);

    $response->assertCreated()->assertJsonStructure(['data' => ['id', 'title', 'slug', 'content', 'author_id']]);
    $response->assertJsonPath('data.author_id', $user2->id);
    $this->assertDatabaseHas('page_version', [
        'page_id' => 1,
    ]);
});
