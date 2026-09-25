<?php

namespace App\Domain\PlayerAccounts\Services;

use App\Domain\CocIntegration\Enums\CocErrorReason;
use App\Domain\CocIntegration\Enums\CocRequestPriority;
use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Domain\CocIntegration\Services\PlayerLookup;
use App\Domain\Notifications\Enums\NoticeKind;
use App\Domain\Notifications\Events\NoticeRequested;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Enums\SnapshotSource;
use App\Domain\PlayerAccounts\Enums\SyncTier;
use App\Domain\PlayerAccounts\Models\CocAccount;
use App\Domain\PlayerAccounts\Models\SyncState;
use App\Support\ValueObjects\PlayerTag;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class AccountSyncService
{
    public function __construct(
        private readonly PlayerLookup $players,
        private readonly SnapshotStore $snapshots,
    ) {}

    public function initialize(int $accountId): void
    {
        $account = CocAccount::query()->find($accountId);
        if ($account === null || $account->status !== CocAccountStatus::Verified) {
            return;
        }

        DB::transaction(function () use ($account): void {
            $state = $this->stateFor($account);
            $tier = $this->tierFor($account, $state);
            $state->forceFill([
                'tier' => $tier->value,
                'next_due_at' => now()->addSeconds($tier->intervalSeconds()),
                'last_success_at' => $account->api_synced_at,
                'consecutive_failures' => 0,
                'not_found_failures' => 0,
                'stale' => false,
                'flagged' => false,
            ])->save();
            $this->snapshots->capture($account, SnapshotSource::Verification);
        });
    }

    public function sync(int $accountId, SnapshotSource $source = SnapshotSource::Scheduled): bool
    {
        $account = CocAccount::query()->find($accountId);
        if ($account === null || $account->user_id === null || ($account->status !== CocAccountStatus::Verified && ! ($source === SnapshotSource::Manual && $account->status === CocAccountStatus::Unverified))) {
            return false;
        }

        $priority = $source === SnapshotSource::Manual ? CocRequestPriority::Manual : CocRequestPriority::Background;

        try {
            $player = $this->players->refresh(new PlayerTag($account->tag), $priority);
            if ($player->stale) {
                throw new CocApiException(CocErrorReason::Timeout, 'The game API is unavailable; saved data was served.');
            }
        } catch (CocApiException $exception) {
            $this->recordFailure($accountId, $exception);

            throw $exception;
        }

        return DB::transaction(function () use ($accountId, $player, $source): bool {
            $account = CocAccount::query()->lockForUpdate()->find($accountId);
            if ($account === null || ($account->status !== CocAccountStatus::Verified && ! ($source === SnapshotSource::Manual && $account->status === CocAccountStatus::Unverified))) {
                return false;
            }

            $account->fill(AccountProgression::fromPlayer($player));
            $account->forceFill(['api_sync_failures' => 0])->save();
            $state = $this->stateFor($account);
            $state->consecutive_failures = 0;
            $tier = $this->tierFor($account, $state);
            $state->forceFill([
                'last_attempt_at' => now(),
                'last_success_at' => now(),
                'consecutive_failures' => 0,
                'not_found_failures' => 0,
                'next_due_at' => now()->addSeconds($tier->intervalSeconds()),
                'tier' => $tier->value,
                'stale' => false,
                'flagged' => false,
            ])->save();

            $changed = $this->snapshots->capture($account, $source);
            Cache::forget('account:'.$account->ulid.':card');

            return $changed;
        });
    }

    public function recordFailure(int $accountId, CocApiException $exception): void
    {
        DB::transaction(function () use ($accountId, $exception): void {
            $account = CocAccount::query()->lockForUpdate()->find($accountId);
            if ($account === null || $account->status !== CocAccountStatus::Verified) {
                return;
            }

            $state = $this->stateFor($account);
            $throttled = $exception->reason === CocErrorReason::Throttled;
            $failures = $throttled ? $state->consecutive_failures : min(32767, $state->consecutive_failures + 1);
            $notFound = $throttled ? $state->not_found_failures : ($exception->isNotFound() ? $state->not_found_failures + 1 : 0);
            $tier = $failures >= (int) config('coc.sync.freeze_after') ? SyncTier::Frozen : $this->tierFor($account, $state);
            $stale = $notFound >= (int) config('coc.sync.not_found_stale_after');
            $flagged = ! $throttled && $tier === SyncTier::Frozen && $state->tier === SyncTier::Frozen;
            $delay = $throttled
                ? max(1, $exception->retryAfter ?? (int) config('coc.sync.retry_seconds'))
                : ($tier === SyncTier::Frozen
                    ? $tier->intervalSeconds()
                    : max((int) config('coc.cache.negative_ttl'), (int) config('coc.sync.retry_seconds') * (2 ** min($failures - 1, 8))));

            $state->forceFill([
                'last_attempt_at' => now(),
                'consecutive_failures' => $failures,
                'not_found_failures' => $notFound,
                'next_due_at' => $flagged ? null : now()->addSeconds($delay),
                'tier' => $tier->value,
                'stale' => $stale,
                'flagged' => $flagged,
            ])->save();
            $account->forceFill(['api_sync_failures' => $failures])->save();

            if ($stale && $notFound === (int) config('coc.sync.not_found_stale_after') && $account->user_id !== null) {
                event(new NoticeRequested((int) $account->user_id, NoticeKind::AccountNotFound));
            }
        });
    }

    public function tierFor(CocAccount $account, SyncState $state): SyncTier
    {
        if ($state->consecutive_failures >= (int) config('coc.sync.freeze_after')) {
            return SyncTier::Frozen;
        }

        if ($account->is_featured || ($state->viewed_at !== null && $state->viewed_at->greaterThan(now()->subHours((int) config('coc.sync.hot_view_hours'))))) {
            return SyncTier::Hot;
        }

        $lastLogin = DB::table('users')->where('id', $account->user_id)->value('last_login_at');
        if ($lastLogin !== null) {
            $lastLoginAt = Carbon::parse($lastLogin);
            if ($lastLoginAt->greaterThan(now()->subDays((int) config('coc.sync.hot_owner_days')))) {
                return SyncTier::Hot;
            }
            if ($lastLoginAt->greaterThan(now()->subDays((int) config('coc.sync.warm_owner_days')))) {
                return SyncTier::Warm;
            }
        }

        return SyncTier::Cold;
    }

    public function recordView(int $accountId): void
    {
        $account = CocAccount::query()->find($accountId);
        if ($account === null || $account->status !== CocAccountStatus::Verified) {
            return;
        }

        $state = $this->stateFor($account);
        if ($state->tier === SyncTier::Frozen) {
            return;
        }
        $state->forceFill([
            'viewed_at' => now(),
            'tier' => SyncTier::Hot->value,
            'next_due_at' => min($state->next_due_at ?? now(), now()->addSeconds(SyncTier::Hot->intervalSeconds())),
        ])->save();
    }

    private function stateFor(CocAccount $account): SyncState
    {
        return SyncState::query()->firstOrCreate(
            ['resource_type' => 'coc_account', 'resource_id' => $account->id],
            ['tier' => SyncTier::Cold->value, 'next_due_at' => now()],
        );
    }
}
