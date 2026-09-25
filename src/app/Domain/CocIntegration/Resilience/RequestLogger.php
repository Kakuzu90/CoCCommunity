<?php

namespace App\Domain\CocIntegration\Resilience;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Writes the coc_api_requests observability log (specs/09 §4): endpoint, status, duration and whether
 * the response was served from cache. It records the key id, never the token, and never the request
 * body — a verify-token call's body holds a secret (specs/09 §9). Pruned at the configured retention.
 */
final class RequestLogger
{
    public function record(
        string $endpoint,
        string $method,
        ?int $status,
        int $durationMs,
        bool $cached,
        ?string $keyId = null,
        ?string $reason = null,
    ): void {
        DB::table('coc_api_requests')->insert([
            'endpoint' => $endpoint,
            'method' => $method,
            'status' => $status,
            'duration_ms' => $durationMs,
            'cached' => $cached,
            'key_id' => $keyId,
            'reason' => $reason,
            'created_at' => Carbon::now(),
        ]);
    }

    /** Delete rows older than the retention window; returns the number removed. */
    public function prune(int $retentionDays): int
    {
        return DB::table('coc_api_requests')
            ->where('created_at', '<', Carbon::now()->subDays($retentionDays))
            ->delete();
    }
}
