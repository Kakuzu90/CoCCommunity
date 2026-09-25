<?php

namespace App\Domain\Audit\Data;

use Illuminate\Http\Request;

/**
 * Request-shaped forensic context stamped onto every audit entry (specs/07 `audit_logs`: ip_hash,
 * user_agent, request_id). Captured from the current request so the caller never has to thread these
 * through the domain. The IP is hashed with sha256 to match the rest of the codebase — audit logs
 * are for correlation, not for storing raw addresses.
 */
final readonly class AuditContext
{
    public function __construct(
        public ?string $ipHash,
        public ?string $userAgent,
        public ?string $requestId,
    ) {}

    public static function fromRequest(?Request $request): self
    {
        if ($request === null) {
            return new self(null, null, null);
        }

        $ip = $request->ip();
        $requestId = $request->attributes->get('request_id');

        return new self(
            ipHash: $ip === null ? null : hash('sha256', $ip),
            userAgent: $request->userAgent(),
            requestId: is_string($requestId) ? $requestId : null,
        );
    }
}
