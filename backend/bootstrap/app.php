<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Force every response to be JSON — no HTML ever returned
        $middleware->prepend(\App\Http\Middleware\ForceJsonResponse::class);

        // Sanctum stateful SPA auth (session + CSRF) for web routes
        $middleware->statefulApi();

        // Named middleware aliases
        $middleware->alias([
            'validate.app.key' => \App\Http\Middleware\ValidateAppKey::class,
        ]);

        // Session hijack protection (only applies to session-authenticated requests)
        $middleware->appendToGroup('api', \App\Http\Middleware\PreventSessionHijacking::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Always render JSON — never HTML
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => true);
    })->create();
