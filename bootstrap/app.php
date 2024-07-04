<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use \App\Http\Middleware\ForceJsonResponse;
use \App\Http\Middleware\Cors;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            ForceJsonResponse::class,
            Cors::class,
        ]);
        // $middleware->api(append: [
        //     ForceJsonResponse::class,
        //     Cors::class,
        // ]);
        $middleware->alias([
            'json.response' => \App\Http\Middleware\ForceJsonResponse::class,
            'cors' => \App\Http\Middleware\Cors::class,
            'api.admin' => \App\Http\Middleware\AdminAuth::class,
            'api.superAdmin' => \App\Http\Middleware\SuperAdminAuth::class, 
        ]);
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
