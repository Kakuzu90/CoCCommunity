<?php

namespace App\Domain\PlayerAccounts\Services;

use App\Domain\CocIntegration\Enums\CocErrorReason;
use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Domain\PlayerAccounts\Enums\CocAccountStatus;
use App\Domain\PlayerAccounts\Enums\RefreshOutcome;
use App\Domain\PlayerAccounts\Enums\SnapshotSource;
use App\Domain\PlayerAccounts\Jobs\SyncCocAccountJob;
use App\Domain\PlayerAccounts\Models\CocAccount;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

final class ManualAccountRefresh
{
    public function __construct(private readonly AccountSyncService $sync) {}

    public function refresh(int $userId, int $accountId): RefreshOutcome
    {
        $account = CocAccount::query()
            ->where('id', $accountId)
            ->where('user_id', $userId)
            ->whereIn('status', [CocAccountStatus::Verified->value, CocAccountStatus::Unverified->value])
            ->firstOrFail();

        $key = 'coc-refresh:'.$userId.':'.$account->id;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            throw new RuntimeException('This account was refreshed recently. Try again in '.RateLimiter::availableIn($key).' seconds.');
        }
        RateLimiter::hit($key, (int) config('coc.sync.manual_cooldown_seconds'));

        try {
            $this->sync->sync((int) $account->id, SnapshotSource::Manual);
        } catch (CocApiException $exception) {
            if ($exception->reason !== CocErrorReason::Timeout) {
                throw $exception;
            }

            SyncCocAccountJob::dispatch((int) $account->id);

            return RefreshOutcome::Queued;
        }

        return RefreshOutcome::Refreshed;
    }
}
