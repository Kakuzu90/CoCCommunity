<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Assigns a request id early so every log line in the request carries it (NFR-OBS-1). Honours an
 * inbound `X-Request-Id` (set by an edge proxy) or mints one, shares it with the logger through
 * Context, and echoes it on the response for cross-tier correlation.
 */
final class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $inbound = (string) $request->headers->get('X-Request-Id', '');
        $requestId = $inbound !== '' && Str::length($inbound) <= 128 ? $inbound : (string) Str::uuid();

        $request->attributes->set('request_id', $requestId);
        Context::add('request_id', $requestId);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
