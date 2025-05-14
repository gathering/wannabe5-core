<?php

namespace App\Providers;

use App\Services\TokenGuard;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use KeycloakGuard\KeycloakGuard;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::extend('wannabe5', function (Application $app, string $name, array $config) {
            $request = $app->request;
            if ($request->header('Authorization') !== null and str_starts_with($request->header('Authorization'), 'Basic')) {
                return new TokenGuard($request);
            }

            return new KeycloakGuard(Auth::createUserProvider($config['provider']), $request);
        });
    }
}
