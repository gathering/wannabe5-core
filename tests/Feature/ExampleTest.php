<?php

test('the application index returns a successful response', function () {
    $this->get('/')
        ->assertOk()
        ->assertJson([
            'name' => config('app.name'),
        ]);
});

test('liveness is alive', function () {
    $response = $this->get('/liveness')->assertOk();
});
