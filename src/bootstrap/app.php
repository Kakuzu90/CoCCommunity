<?php

use App\Http\Controllers\Ops\HealthController;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\LogRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Readiness endpoint (NFR-OBS-4) registered outside the web group: a public uptime probe
        // must not open a session or set a cookie on every poll. Global middleware (the request id)
        // still applies; `/up` remains the bare liveness probe.
        then: function (): void {
            Route::get('/health', HealthController::class)->name('health');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Correlation id first so every downstream log line carries it; the access log runs on
        // terminate, after the status and user are known (NFR-OBS-1).
        $middleware->prepend(AssignRequestId::class);
        $middleware->append(LogRequests::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Tag every reported exception with the release and request id (NFR-OBS-2). This is the one
        // place a Sentry (or equivalent) client slots in; today errors flow to the log channels.
        $exceptions->report(function (Throwable $e): void {
            Context::add('release', (string) config('health.release'));
        });
    })->create();
