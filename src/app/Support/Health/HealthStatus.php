<?php

namespace App\Support\Health;

/** The state of one health check, ordered worst-last so an overall status is the max (specs/20 §6). */
enum HealthStatus: string
{
    case Ok = 'ok';
    case Degraded = 'degraded';
    case Unknown = 'unknown';
    case Down = 'down';

    public function severity(): int
    {
        return match ($this) {
            self::Ok => 0,
            self::Unknown => 1,
            self::Degraded => 2,
            self::Down => 3,
        };
    }

    /** A down check is the only one that fails liveness; everything else still serves 200. */
    public function isFailure(): bool
    {
        return $this === self::Down;
    }
}
