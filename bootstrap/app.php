<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\LocalizationMiddleware::class,
        ]);

        $middleware->alias([
            'check.step.access' =>  \App\Http\Middleware\CheckStepAccess::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
