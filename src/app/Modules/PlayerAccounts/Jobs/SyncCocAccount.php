<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Jobs;

use App\Modules\CocIntegration\Contracts\ClashClient;
use App\Modules\CocIntegration\Exceptions\ClashApiUnavailableException;
use App\Modules\CocIntegration\Exceptions\PlayerNotFoundException;
use App\Modules\CocIntegration\Exceptions\RateLimitedException;
use App\Modules\PlayerAccounts\Actions\RecordSnapshot;
use App\Modules\PlayerAccounts\Enums\AccountState;
use App\Modules\PlayerAccounts\Models\CocAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Refreshes an account's snapshot from the API. A tag that no longer resolves
 * flips to needs_reverify (data is kept, syncing stops); transient failures
 * are released for retry.
 */
class SyncCocAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $accountId) {}

    public function handle(ClashClient $client, RecordSnapshot $record): void
    {
        $account = CocAccount::find($this->accountId);

        if ($account === null) {
            return;
        }

        try {
            $player = $client->fetchPlayer($account->tag);
        } catch (PlayerNotFoundException) {
            $account->forceFill(['state' => AccountState::NeedsReverify])->save();

            return;
        } catch (RateLimitedException|ClashApiUnavailableException) {
            $this->release(120);

            return;
        }

        $record->handle($account, $player);
    }
}
