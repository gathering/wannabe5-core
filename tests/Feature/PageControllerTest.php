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
        'content' => '{"test":"test2"}',
        'author_id' => $user->id,
        'slug' => 'test',
    ]);

    $response->assertCreated()->assertJsonStructure(['data' => ['id', 'title', 'slug', 'content', 'author_id']]);
    $response->assertJsonPath('data.author_id', $user->id);
});

test('update page', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create();

    $response = $this->asUser($user)->putJson('/api/page/'.$page->id, [
        'title' => 'new title',
        'content' => '{"test":"test2"}',
        'author_id' => $user->id,
        'slug' => 'newslug',
    ]);

    $response->assertJsonStructure(['data' => ['id', 'title', 'slug', 'content', 'author_id']]);
    $response->assertJsonPath('data.author_id', $user->id);
    $this->assertDatabaseHas('page_versions', [
        'page_id' => $page->id,
    ]);
});

test('fail validation on create page', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create();

    // Fail with non unique slug
    $this->asUser($user)->postJson('/api/page/', [
        'title' => 'test title',
        'content' => '{"test":"test2"}',
        'author_id' => $user->id,
        'slug' => $page->slug,
    ])->assertStatus(422)->assertInvalid(['slug']);

    $this->asUser($user)->postJson('/api/page/', [
        'title' => 'test title',
        'content' => 'invalid json',
        'author_id' => Str::uuid(), // invalid user
        'slug' => 'invalid slug',
    ])->assertStatus(422)->assertInvalid(['content', 'author_id', 'slug']);
});

test('get page versions', function () {
    $user = User::factory()->create();
    $page = Page::factory()->create();

    $response = $this->asUser($user)->getJson("/api/page/{$page->id}/versions");
    $response->assertStatus(200)->assertJsonCount(0, 'data');

    // Update page to create a new version
    $this->asUser($user)->putJson('/api/page/'.$page->id, $page->only(['title', 'content', 'author_id', 'slug']));

    // version_number should now be 1
    $response = $this->asUser($user)->getJson("/api/page/{$page->id}/versions");
    $response->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.version_number', 1);
});
