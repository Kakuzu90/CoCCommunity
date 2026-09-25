<?php

namespace App\Domain\Audit\Data;

use Carbon\CarbonInterface;

/**
 * A single audit-log row shaped for the admin viewer (specs/12 admin AuditTrailList). Carries the
 * actor's username resolved by join so Presentation never touches the User model, and the decoded
 * before/after diff for the DiffViewer.
 */
final readonly class AuditEntry
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        public int $id,
        public string $action,
        public string $actionLabel,
        public ?string $actorUsername,
        public ?string $actorRole,
        public ?string $auditableType,
        public ?int $auditableId,
        public ?string $auditableUsername,
        public ?array $before,
        public ?array $after,
        public ?array $context,
        public ?string $requestId,
        public CarbonInterface $createdAt,
    ) {}
}
