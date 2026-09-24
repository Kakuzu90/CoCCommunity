<?php

namespace App\Support\Health;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Readiness checks for the /health endpoint (NFR-OBS-4) and the platform:check-health command.
 *
 * Cheap, local checks (database, queue depth, scheduler heartbeat) run on every poll. Expensive
 * external reachability (object storage, later the CoC API) is probed on a schedule and cached, so
 * a monitoring poll never blocks on a network round-trip or turns a slow bucket into a 503 storm
 * (specs/20 §2 CheckExternalHealthJob → health endpoint state).
 */
final class HealthReporter
{
    /** Live: can we reach the database at all? This is the only check that fails liveness. */
    public function database(): HealthCheck
    {
        try {
            DB::select('select 1');

            return new HealthCheck('database', HealthStatus::Ok, 'reachable');
        } catch (Throwable $e) {
            return new HealthCheck('database', HealthStatus::Down, 'unreachable');
        }
    }

    /** Live: queue backlog, failed-job count and the scheduler heartbeat (specs/20 §6). */
    public function queue(): HealthCheck
    {
        try {
            $depth = (int) DB::table('jobs')->count();
            $failed = (int) DB::table('failed_jobs')->count();
        } catch (Throwable $e) {
            return new HealthCheck('queue', HealthStatus::Down, 'queue tables unreadable');
        }

        $backlogLimit = (int) config('health.queue_backlog_degraded');
        $failedLimit = (int) config('health.failed_jobs_degraded');
        $scheduler = $this->schedulerHeartbeat();

        $status = HealthStatus::Ok;
        $notes = [];
        if ($depth > $backlogLimit) {
            $status = HealthStatus::Degraded;
            $notes[] = "backlog {$depth} > {$backlogLimit}";
        }
        if ($failed > $failedLimit) {
            $status = HealthStatus::Degraded;
            $notes[] = "failed {$failed} > {$failedLimit}";
        }
        if ($scheduler['status'] !== HealthStatus::Ok) {
            $status = $this->worst($status, $scheduler['status']);
            $notes[] = $scheduler['message'];
        }

        return new HealthCheck(
            'queue',
            $status,
            $notes === [] ? 'flowing' : implode('; ', $notes),
            ['depth' => $depth, 'failed' => $failed, 'scheduler' => $scheduler['message']],
        );
    }

    /** Cached: the last external reachability result written by platform:check-health. */
    public function storage(): HealthCheck
    {
        /** @var array{status: string, message: string, at?: string}|null $cached */
        $cached = Cache::get((string) config('health.cache.external'));
        if ($cached === null) {
            return new HealthCheck('storage', HealthStatus::Unknown, 'no check has run yet');
        }

        $status = HealthStatus::tryFrom($cached['status']) ?? HealthStatus::Unknown;

        return new HealthCheck('storage', $status, $cached['message'], ['checked_at' => $cached['at'] ?? null]);
    }

    /** Live probe of object-storage reachability — run on a schedule, then cached for /health. */
    public function probeStorage(): HealthCheck
    {
        $disk = (string) config('health.storage_disk');

        try {
            // A HEAD for a key that need not exist: it either answers or throws. Either way the
            // provider is reachable; a thrown transport error is not.
            Storage::disk($disk)->exists('.health-probe');

            return new HealthCheck('storage', HealthStatus::Ok, 'reachable');
        } catch (Throwable $e) {
            return new HealthCheck('storage', HealthStatus::Down, 'unreachable');
        }
    }

    /**
     * Assemble the readiness report. HTTP is 503 only when the database is down — the one failure
     * that means the app cannot function — while storage/queue degradation is surfaced in the body
     * for finer alerting without paging on a slow bucket.
     *
     * @return array{status: string, http: int, checks: array<string, array<string, mixed>>, release: string}
     */
    public function summary(): array
    {
        $database = $this->database();
        $checks = [$database, $this->queue(), $this->storage()];

        $overall = HealthStatus::Ok;
        $out = [];
        foreach ($checks as $check) {
            $overall = $this->worst($overall, $check->status);
            $out[$check->name] = $check->toArray();
        }

        return [
            'status' => $overall->value,
            'http' => $database->status->isFailure() ? 503 : 200,
            'checks' => $out,
            'release' => (string) config('health.release'),
        ];
    }

    /** @return array{status: HealthStatus, message: string} */
    private function schedulerHeartbeat(): array
    {
        $beat = Cache::get((string) config('health.cache.scheduler'));
        if (! is_string($beat)) {
            return ['status' => HealthStatus::Unknown, 'message' => 'scheduler: no heartbeat yet'];
        }

        $age = CarbonImmutable::now()->diffInSeconds(CarbonImmutable::parse($beat), true);
        if ($age > (int) config('health.scheduler_stale_after')) {
            return ['status' => HealthStatus::Down, 'message' => "scheduler: stale ({$age}s)"];
        }

        return ['status' => HealthStatus::Ok, 'message' => 'scheduler: alive'];
    }

    private function worst(HealthStatus $a, HealthStatus $b): HealthStatus
    {
        return $a->severity() >= $b->severity() ? $a : $b;
    }
}
