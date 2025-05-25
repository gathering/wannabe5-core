<?php

test('the application index returns a successful response', function () {
    $this->get('/api')
        ->assertOk()
        ->assertJson([
            'name' => config('app.name'),
        ]);
});

test('up is alive', function () {
    $response = $this->get('/up')->assertOk();
});
