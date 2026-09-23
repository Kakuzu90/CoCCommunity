<?php

declare(strict_types=1);

namespace App\Modules\PlayerAccounts\Jobs;

use App\Modules\CocIntegration\Contracts\ClashClient;
use App\Modules\CocIntegration\Exceptions\ClashApiUnavailableException;
use App\Modules\CocIntegration\Exceptions\PlayerNotFoundException;
use App\Modules\CocIntegration\Exceptions\RateLimitedException;
use App\Modules\Notifications\Services\Notifier;
use App\Modules\PlayerAccounts\Actions\GrantOwnership;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs the token check off the request path. On success it grants ownership
 * (create or audited transfer); on an invalid token it notifies the user;
 * transient API failures are released for retry with backoff.
 */
class VerifyCocToken implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly int $userId,
        public readonly string $tag,
        public readonly string $token,
    ) {}

    public function handle(ClashClient $client, GrantOwnership $grant, Notifier $notifier): void
    {
        try {
            $verified = $client->verifyToken($this->tag, $this->token);
        } catch (RateLimitedException|ClashApiUnavailableException) {
            $this->release(60);

            return;
        } catch (PlayerNotFoundException) {
            $verified = false;
        }

        if ($verified) {
            $grant->handle($this->userId, $this->tag);

            return;
        }

        $notifier->push($this->userId, 'coc.verification_failed', ['tag' => $this->tag]);
    }
}
