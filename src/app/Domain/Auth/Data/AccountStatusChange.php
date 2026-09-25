<?php

namespace App\Domain\Auth\Data;

use App\Domain\Auth\Enums\UserStatus;
use App\Domain\Auth\Services\UserAccountAdmin;

/**
 * The outcome of an authorized account-status transition (specs/04 §2 sanction rows). Returned by
 * {@see UserAccountAdmin} so the caller can record the before/after states
 * to the moderation and audit trails without re-reading the User model.
 */
final readonly class AccountStatusChange
{
    public function __construct(
        public int $targetId,
        public string $targetUsername,
        public int $actorId,
        public string $actorRole,
        public UserStatus $before,
        public UserStatus $after,
    ) {}
}
