<?php

use App\Http\Middleware\ForceJsonResponse;
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
        // Global middleware (applied to all requests)
        // $middleware->append(SomeGlobalMiddleware::class);

        // Web middleware group
        $middleware->web(append: [
            // Add web-specific middleware here if needed
        ]);

        // API middleware group - this is where we configure API-specific middleware
        $middleware->api(prepend: [
            ForceJsonResponse::class, // Force JSON responses for all API routes
        ]);

        // You can also remove default middleware if needed
        // $middleware->api(remove: [
        //     \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        // ]);

        // Register route-specific middleware aliases
        $middleware->alias([
            'force.json' => ForceJsonResponse::class,
            // Add other middleware aliases here
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
