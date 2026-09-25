<?php

namespace App\Domain\Auth\Data;

use App\Domain\Auth\Enums\UserRole;
use App\Domain\Auth\Enums\UserStatus;
use Carbon\CarbonInterface;

/**
 * The full account context an admin needs on the user detail screen (specs/12 §4 "Author context"):
 * status with its reason and end date, account age, verified accounts, published content, and the
 * current deletion state. Sanction history is composed separately from the Moderation module.
 */
final readonly class AdminUserDetail
{
    public function __construct(
        public int $id,
        public string $username,
        public string $email,
        public UserRole $role,
        public UserStatus $status,
        public ?string $statusReason,
        public ?CarbonInterface $statusExpiresAt,
        public bool $emailVerified,
        public int $verifiedAccounts,
        public int $basesPublished,
        public CarbonInterface $createdAt,
        public ?CarbonInterface $lastLoginAt,
        public ?CarbonInterface $deletionRequestedAt,
    ) {}
}
