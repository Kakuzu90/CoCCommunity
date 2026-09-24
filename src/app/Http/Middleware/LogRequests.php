<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * One structured line per request — method, route, status and duration (NFR-OBS-1). Written on
 * terminate so the status and authenticated user are known, to a JSON channel that also carries the
 * request id and user id from Context. Uptime polls (/up, /health) are skipped to keep the log clean.
 */
final class LogRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (in_array($request->path(), (array) config('health.unlogged_paths'), true)) {
            return;
        }

        // Fall back to the discard channel when none is configured (the test default,
        // LOG_REQUEST_CHANNEL=null, resolves to null): `Log::channel('')` would be an invalid name.
        $channel = (string) config('logging.request_channel') ?: 'null';

        $userId = Auth::id();
        if ($userId !== null) {
            Context::add('user_id', $userId);
        }

        $start = defined('LARAVEL_START') ? LARAVEL_START : $request->server('REQUEST_TIME_FLOAT');
        $durationMs = $start ? (int) round((microtime(true) - (float) $start) * 1000) : null;

        Log::channel($channel)->info('request.handled', [
            'method' => $request->getMethod(),
            'path' => '/'.ltrim($request->path(), '/'),
            'route' => optional($request->route())->getName(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'ip' => $request->ip(),
        ]);
    }
}
