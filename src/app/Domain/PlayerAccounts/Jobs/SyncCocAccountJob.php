<?php

namespace App\Domain\PlayerAccounts\Jobs;

use App\Domain\CocIntegration\Enums\CocErrorReason;
use App\Domain\CocIntegration\Exceptions\CocApiException;
use App\Domain\PlayerAccounts\Services\AccountSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

final class SyncCocAccountJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(public readonly int $accountId)
    {
        $this->onQueue((string) config('coc.sync.queue'));
    }

    public function uniqueId(): string
    {
        return (string) $this->accountId;
    }

    public function uniqueFor(): int
    {
        return (int) config('coc.sync.unique_seconds');
    }

    public function tries(): int
    {
        return (int) config('coc.sync.tries');
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return array_values(array_map('intval', (array) config('coc.sync.backoff')));
    }

    /** @return list<WithoutOverlapping> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('coc-account-'.$this->accountId))
            ->releaseAfter((int) config('coc.sync.retry_seconds'))
            ->expireAfter((int) config('coc.sync.overlap_seconds'))];
    }

    public function handle(AccountSyncService $sync): void
    {
        try {
            $sync->sync($this->accountId);
        } catch (CocApiException $exception) {
            if ($exception->reason === CocErrorReason::NotFound) {
                return;
            }

            $this->release(max(1, $exception->retryAfter ?? $this->backoff()[min($this->attempts() - 1, count($this->backoff()) - 1)]));
        }
    }
}
