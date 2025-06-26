<?php

use App\Http\Controllers\IndexController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PageVersionController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', [IndexController::class, 'index']);

// protected endpoints
Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/test', function () {
        return response()->json(Auth::user());
    });
    Route::get('/metrics', [MetricsController::class, 'index']);

    Route::apiResources([
        'profile' => UserProfileController::class,
        // TODO: Event based routing?
        'page' => PageController::class,
        'page.versions' => PageVersionController::class,
    ]);
});
