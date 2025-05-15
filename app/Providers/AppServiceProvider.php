<?php

namespace App\Providers;

use App\Services\TokenGuard;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
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
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->components->securitySchemes['basic'] = SecurityScheme::http('basic');
                $openApi->components->securitySchemes['bearer'] = SecurityScheme::http('bearer', 'JWT');

                $openApi->security[] = new SecurityRequirement([
                    'basic' => [],
                ]);
                $openApi->security[] = new SecurityRequirement([
                    'bearer' => [],
                ]);
            });
    }
}
