<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
     // Banned Middleware   
    $middleware->web(append: [\App\Http\Middleware\CheckBanned::class,]);

    // Admin Middleware
    $middleware->alias([
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
    'timezone' =>\App\Http\Middleware\SetUsertimezone::class,
    ]);

    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
