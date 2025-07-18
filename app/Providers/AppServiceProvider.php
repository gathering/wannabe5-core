<?php

namespace App\Providers;

use App\Models\User;
use App\Models\UserProfile;
use App\Services\TokenGuard;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use KeycloakGuard\KeycloakGuard;
use Ramsey\Uuid\Uuid;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Auth::extend('wannabe5', function (Application $app, string $name, array $config) {
            $request = $app->request;
            if ($request->header('Authorization') !== null and str_starts_with($request->header('Authorization'), 'Basic')) {
                return new TokenGuard($request);
            }

            return new KeycloakGuard(new CustomUserProvider(new BcryptHasher, User::class), $request);
        });

        Route::bind('profile', function (string $value) {
            if (is_numeric($value)) {
                return UserProfile::findOrFail($value);
            } elseif (Uuid::isValid($value)) {
                return UserProfile::where('user_id', $value)->firstOrFail();
            }

            return null;
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
                $openApi->components->securitySchemes['basic']->description = 'Wannabe5 Core Access Token.  
                    Use User UUID as username and token as password.';
                $openApi->components->securitySchemes['bearer']->description = 'Wannabe5 Keycloak JWT';
            });
    }
}
