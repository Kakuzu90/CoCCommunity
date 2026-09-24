<?php

namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use App\Support\Health\HealthReporter;
use Illuminate\Http\JsonResponse;

/**
 * Readiness endpoint for uptime monitoring (NFR-OBS-4). Unauthenticated and minimal: it reports
 * the state of database, queue and storage, and 503s only when the database is down. Laravel's
 * built-in `/up` remains the bare liveness probe.
 */
final class HealthController extends Controller
{
    public function __invoke(HealthReporter $reporter): JsonResponse
    {
        $report = $reporter->summary();

        return response()->json([
            'status' => $report['status'],
            'release' => $report['release'],
            'checks' => $report['checks'],
        ], $report['http']);
    }
}
