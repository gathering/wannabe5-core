<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use KeycloakGuard\Exceptions\TokenException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Do not redirect to /login if not_authenticated
        $middleware->redirectGuestsTo(function (Request $request) {
            return response()->json(['status' => 'error', 'hint' => 'not_authenticated'], 403);
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // /api/* should always return as JSON
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->expectsJson() || $request->is('api/*');
        });

        $exceptions->dontReport([
            TokenException::class,
        ]);

        // Do not throw exceptions on TokenException
        $exceptions->render(function (TokenException $e, Request $request) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 401);
        });
    })->create();
