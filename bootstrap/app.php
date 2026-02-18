<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        // 1. Register Global/Web Middleware (Inertia Bridge)
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        // 2. Configure CSRF Exceptions (Camera API)
        $middleware->validateCsrfTokens(except: [
            'posture-chunks', 
            'posture-chunks/*', 
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();