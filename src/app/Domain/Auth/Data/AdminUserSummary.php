<?php

namespace App\Domain\Auth\Data;

use App\Domain\Auth\Enums\UserRole;
use App\Domain\Auth\Enums\UserStatus;
use Carbon\CarbonInterface;

/** A user row for the admin DataTable (specs/12 §4, specs/18 §4). DTO — the model never leaves Auth. */
final readonly class AdminUserSummary
{
    public function __construct(
        public int $id,
        public string $username,
        public string $email,
        public UserRole $role,
        public UserStatus $status,
        public int $verifiedAccounts,
        public CarbonInterface $createdAt,
        public ?CarbonInterface $lastLoginAt,
    ) {}
}
