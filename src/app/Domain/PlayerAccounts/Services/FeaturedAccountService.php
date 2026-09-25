<?php

namespace App\Domain\PlayerAccounts\Services;

use App\Domain\Audit\Data\AuditContext;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Services\AuditLogger;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Models\CocAccount;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * The one featured account per user (FR-COC-12, specs/13 §3). The flag lives on `coc_accounts.is_featured`
 * behind a partial unique index, so every write clears the old flag before setting the new one.
 *
 * A featured account must be verified or under review. `reconcile()` restores that after any change that
 * can strip it (detach, supersede, dispute transfer) by promoting the most recently verified account.
 */
final class FeaturedAccountService
{
    /** Statuses that may keep the featured flag. A disputed account stays featured while under review. */
    private const KEEPS_FLAG = [CocAccountStatus::Verified, CocAccountStatus::Disputed];

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws ModelNotFoundException when the account is not the user's or not verified (IDOR-safe)
     */
    public function feature(int $userId, int $accountId, ?AuditContext $context = null): void
    {
        $context ??= AuditContext::fromRequest(request());

        DB::transaction(function () use ($userId, $accountId, $context): void {
            $account = CocAccount::query()
                ->where('id', $accountId)
                ->where('user_id', $userId)
                ->where('status', CocAccountStatus::Verified->value)
                ->lockForUpdate()
                ->firstOrFail();

            if ($account->is_featured) {
                return;
            }

            $previousId = CocAccount::query()->where('user_id', $userId)->where('is_featured', true)->value('id');
            CocAccount::query()->where('user_id', $userId)->where('is_featured', true)->update(['is_featured' => false]);
            $account->forceFill(['is_featured' => true])->save();

            $this->audit->record(
                actorId: $userId,
                actorRole: null,
                action: AuditAction::CocAccountFeatured,
                auditableType: (new CocAccount)->getMorphClass(),
                auditableId: $accountId,
                before: ['featured_account_id' => $previousId],
                after: ['featured_account_id' => $accountId],
                context: $context,
            );
        });
    }

    /** Make sure the user has a valid featured account if they have any verified one. Call inside the caller's transaction. */
    public function reconcile(int $userId): void
    {
        $featured = CocAccount::query()->where('user_id', $userId)->where('is_featured', true)->first();
        if ($featured !== null && in_array($featured->status, self::KEEPS_FLAG, true)) {
            return;
        }

        $featured?->forceFill(['is_featured' => false])->save();

        CocAccount::query()
            ->where('user_id', $userId)
            ->where('status', CocAccountStatus::Verified->value)
            ->orderByDesc('verified_at')
            ->orderByDesc('id')
            ->first()
            ?->forceFill(['is_featured' => true])
            ->save();
    }
}
