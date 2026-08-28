<?php

use App\Http\Middleware\EnsureCompanyManager;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('manage') || $request->is('manage/*')
            ? route('manage.login')
            : route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->routeIs('manage.login*')
            ? route('manage.companies.index')
            : route('dashboard'));

        $middleware->alias([
            'role' => EnsureRole::class,
            'company_manager' => EnsureCompanyManager::class,
            'password.changed' => EnsurePasswordChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
