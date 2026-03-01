<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Suppress PHP 8.5 deprecation warnings for PDO constants until Laravel updates
error_reporting(E_ALL ^ E_DEPRECATED);

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',   // register v1 API routes (was missing)
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Redirect unauthenticated web requests to Filament's login page.
        // Laravel's default Authenticate middleware calls route('login'), which does NOT
        // exist — this app uses Filament (filament.admin.auth.login) instead of a vanilla
        // login page. Without this, unauthenticated access throws RouteNotFoundException (500).
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));

        // Apply security headers to every response
        $middleware->append(App\Http\Middleware\SecurityHeaders::class);

        // Apply CORS only to the API middleware group (reads config/cors.php)
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
