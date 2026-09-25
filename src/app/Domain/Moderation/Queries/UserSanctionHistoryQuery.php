<?php

namespace App\Domain\Moderation\Queries;

use App\Domain\Moderation\Data\SanctionSummary;
use App\Domain\Moderation\Enums\ReasonCode;
use App\Domain\Moderation\Enums\SanctionType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Prior-sanction context for the admin user detail screen (specs/12 §4 "Author context"). Returns
 * DTOs with issuer/lifter usernames resolved by join, newest first. A sanction is active when it has
 * not been lifted and has not expired.
 */
final class UserSanctionHistoryQuery
{
    /** @return list<SanctionSummary> */
    public function forUser(int $userId): array
    {
        return array_values(DB::table('user_sanctions')
            ->leftJoin('users as issuers', 'issuers.id', '=', 'user_sanctions.issued_by')
            ->leftJoin('users as lifters', 'lifters.id', '=', 'user_sanctions.lifted_by')
            ->where('user_sanctions.user_id', $userId)
            ->orderByDesc('user_sanctions.id')
            ->select(
                'user_sanctions.*',
                'issuers.username as issued_by_username',
                'lifters.username as lifted_by_username',
            )
            ->get()
            ->map(fn (\stdClass $row): SanctionSummary => $this->toSummary($row))
            ->all());
    }

    public function activeCount(int $userId): int
    {
        $now = CarbonImmutable::now();

        return (int) DB::table('user_sanctions')
            ->where('user_id', $userId)
            ->whereNull('lifted_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', $now))
            ->count();
    }

    private function toSummary(\stdClass $row): SanctionSummary
    {
        $expiresAt = $row->expires_at === null ? null : CarbonImmutable::parse($row->expires_at);
        $liftedAt = $row->lifted_at === null ? null : CarbonImmutable::parse($row->lifted_at);
        $active = $liftedAt === null && ($expiresAt === null || $expiresAt->isFuture());

        return new SanctionSummary(
            id: (int) $row->id,
            type: SanctionType::from($row->type),
            reasonCode: ReasonCode::from($row->reason_code),
            publicReason: $row->public_reason,
            internalNote: $row->internal_note,
            issuedByUsername: $row->issued_by_username,
            liftedByUsername: $row->lifted_by_username,
            startsAt: CarbonImmutable::parse($row->starts_at),
            expiresAt: $expiresAt,
            liftedAt: $liftedAt,
            active: $active,
        );
    }
}
