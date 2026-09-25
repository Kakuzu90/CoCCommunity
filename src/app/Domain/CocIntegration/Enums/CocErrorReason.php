<?php

namespace App\Domain\CocIntegration\Enums;

/**
 * Why a CoC API call failed, mapped from HTTP status + the API's own `reason` string (specs/09 §7).
 * Downstream code branches on this, never on a raw status code or Supercell's JSON.
 */
enum CocErrorReason: string
{
    case NotFound = 'not_found';               // 404 notFound — tag does not exist
    case AccessDenied = 'access_denied';       // 403 accessDenied — bad/expired key
    case InvalidIp = 'invalid_ip';             // 403 accessDenied.invalidIp — key not bound to this IP
    case Throttled = 'throttled';              // 429 — over rate
    case Maintenance = 'maintenance';          // 503 inMaintenance
    case ServerError = 'server_error';         // 5xx
    case Timeout = 'timeout';                  // connect/total timeout or transport error
    case Malformed = 'malformed';              // 200 with an unexpected shape
    case CircuitOpen = 'circuit_open';         // breaker is open; no call was attempted
    case NoHealthyKey = 'no_healthy_key';      // the key pool is empty of healthy keys

    /** True when the caller should serve cached/snapshot data rather than surface an error. */
    public function isTransient(): bool
    {
        return match ($this) {
            self::NotFound => false,
            default => true,
        };
    }

    /** True when the failure means the key pool or IP binding is broken and needs an operator alert. */
    public function isKeyFailure(): bool
    {
        return $this === self::AccessDenied || $this === self::InvalidIp;
    }
}
