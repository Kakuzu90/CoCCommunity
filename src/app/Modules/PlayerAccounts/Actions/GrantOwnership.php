<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Actions;

use App\Modules\Moderation\Services\AuditLogger;
use App\Modules\Notifications\Services\Notifier;
use App\Modules\PlayerAccounts\Enums\AccountState;
use App\Modules\PlayerAccounts\Events\AccountOwnershipTransferred;
use App\Modules\PlayerAccounts\Events\AccountVerified;
use App\Modules\PlayerAccounts\Jobs\SyncCocAccount;
use App\Modules\PlayerAccounts\Models\CocAccount;
use Illuminate\Support\Facades\DB;

/**
 * Marks a tag verified for a user after the token has already been confirmed.
 * Current token control wins: an existing owner is transferred out, audited,
 * and notified. Every path writes the audit trail and queues a fresh sync.
 */
class GrantOwnership
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly Notifier $notifier,
    ) {}

    public function handle(int $userId, string $tag): CocAccount
    {
        $account = DB::transaction(function () use ($userId, $tag): CocAccount {
            $account = CocAccount::withTrashed()->where('tag', $tag)->lockForUpdate()->first();

            $previousOwnerId = $account?->user_id;
            $isTransfer = $account !== null && $previousOwnerId !== $userId;

            if ($account === null) {
                $account = new CocAccount(['tag' => $tag]);
            }

            if ($account->trashed()) {
                $account->restore();
            }

            $account->forceFill([
                'user_id' => $userId,
                'state' => AccountState::Verified,
                'verified_at' => now(),
            ])->save();

            if ($isTransfer) {
                $this->audit->log(
                    actorId: $userId,
                    action: 'coc_account.ownership_transferred',
                    subject: $account,
                    before: ['user_id' => $previousOwnerId],
                    after: ['user_id' => $userId],
                );

                if ($previousOwnerId !== null) {
                    $this->notifier->push($previousOwnerId, 'coc.ownership_lost', ['tag' => $tag]);
                }

                event(new AccountOwnershipTransferred($account->id, $previousOwnerId, $userId));
            } else {
                $this->audit->log(
                    actorId: $userId,
                    action: 'coc_account.verified',
                    subject: $account,
                    after: ['state' => AccountState::Verified->value],
                );
            }

            event(new AccountVerified($account->id, $userId));

            return $account;
        });

        SyncCocAccount::dispatch($account->id);

        return $account;
    }
}
