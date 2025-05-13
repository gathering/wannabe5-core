<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertExactJson([
            'Wannabe5-Core' => 'alpha0',
        ]);
});
