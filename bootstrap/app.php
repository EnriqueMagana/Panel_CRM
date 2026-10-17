<?php

use App\Http\Middleware\EnforceSessionTimeout;
use App\Http\Middleware\EnsureSidebarModuleIsActive;
use App\Http\Middleware\EnsureUserAccountIsActive;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\PreventBackHistory;
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
        $middleware->web(append: [
            PreventBackHistory::class,
            EnsureUserAccountIsActive::class,
            EnforceSessionTimeout::class,
            ForcePasswordChange::class,
        ]);
        $middleware->alias(['sidebar.active' => EnsureSidebarModuleIsActive::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
