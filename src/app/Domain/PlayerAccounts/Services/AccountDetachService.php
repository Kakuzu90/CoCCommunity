<?php

namespace App\Domain\PlayerAccounts\Services;

use App\Domain\Audit\Data\AuditContext;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Auth\Services\VerifiedAccountCounter;
use App\Domain\Notifications\Enums\NoticeKind;
use App\Domain\Notifications\Events\NoticeRequested;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Models\CocAccount;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Detach an account and release its tag (specs/13 §6). Password re-confirmation is enforced at the
 * request boundary (the `current_password` rule); this service performs the state change. The row is
 * kept (not deleted) with its snapshots so a later re-attach reuses it and history stays continuous;
 * the tag becomes immediately claimable by anyone with a token.
 *
 * @throws ModelNotFoundException when the account is not the user's (IDOR-safe)
 */
final class AccountDetachService
{
    public function __construct(
        private readonly VerifiedAccountCounter $counter,
        private readonly AuditLogger $audit,
    ) {}

    public function detach(int $userId, int $accountId, ?AuditContext $context = null): void
    {
        $context ??= AuditContext::fromRequest(request());

        $account = CocAccount::query()
            ->where('id', $accountId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $wasVerified = $account->status === CocAccountStatus::Verified;
        $originalStatus = $account->status->value;

        DB::transaction(function () use ($account, $userId, $accountId, $wasVerified, $originalStatus, $context): void {
            $account->forceFill([
                'status' => CocAccountStatus::Released->value,
                'user_id' => null,
                'is_featured' => false,
            ])->save();

            if ($wasVerified) {
                $this->counter->decrement($userId);
            }

            $this->audit->record(
                actorId: $userId,
                actorRole: null,
                action: AuditAction::CocAccountDetached,
                auditableType: (new CocAccount)->getMorphClass(),
                auditableId: $accountId,
                before: ['user_id' => $userId, 'status' => $originalStatus],
                after: ['user_id' => null, 'status' => CocAccountStatus::Released->value],
                context: $context,
            );

            event(new NoticeRequested($userId, NoticeKind::TagReleased));
        });
    }
}
