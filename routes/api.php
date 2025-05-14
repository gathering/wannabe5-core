<?php

use App\Http\Controllers\MetricsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'env' => config('app.env'),
        'debug' => config('app.debug'),
        'url' => config('app.url'),
        'timezone' => config('app.timezone'),
    ]);
});

Route::get('/liveness', function () {
    return response('ok', 200);
});

Route::get('/api/metrics', [MetricsController::class, 'index']);

// protected endpoints
Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/api/test', function () {
        return response()->json(Auth::user());
    });
});
