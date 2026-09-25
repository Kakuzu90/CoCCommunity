<?php

namespace App\Console\Commands;

use App\Domain\CocIntegration\Services\CocHealthProbe;
use Illuminate\Console\Command;

/**
 * Startup readiness gate (specs/09 §3): the app is not ready until the key pool has at least one healthy
 * key. Non-zero exit lets a deploy or orchestrator hold traffic. The fake driver has no keys by design,
 * so it passes — local and CI never need a real key (CLAUDE.md local-first).
 */
class CocVerifyKeys extends Command
{
    protected $signature = 'coc:verify-keys';

    protected $description = 'Verify the CoC API key pool has at least one healthy key';

    public function handle(CocHealthProbe $probe): int
    {
        if (config('coc.driver') === 'fake') {
            $this->info('coc:verify-keys driver=fake — no key required.');

            return self::SUCCESS;
        }

        $check = $probe->check();
        $this->line("coc:verify-keys {$check->status->value} ({$check->message})");

        if (! $probe->hasHealthyKey()) {
            $this->error('No healthy CoC API key available — the integration is degraded to snapshots.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
