<?php

return [

    'defaults' => [
        'guard' => 'api',
    ],

    'guards' => [
        'api' => [
            'driver' => 'wannabe5',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

];
