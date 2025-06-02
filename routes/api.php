<?php

use App\Http\Controllers\IndexController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IndexController::class, 'index']);

// protected endpoints
Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/test', function () {
        return response()->json(Auth::user());
    });
    Route::get('/metrics', [MetricsController::class, 'index']);
    Route::get('/profile', [UserProfileController::class, 'index']);
});
