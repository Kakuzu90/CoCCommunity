<?php

namespace App\Console\Commands;

use App\Domain\GameAssets\Exceptions\AssetPackException;
use App\Domain\GameAssets\Services\AssetPackVerifier;
use Illuminate\Console\Command;

/** Weekly integrity audit (specs/10 §9): the bucket must still match the committed manifest. */
class AssetsVerifyPack extends Command
{
    protected $signature = 'assets:verify-pack';

    protected $description = 'Verify every game asset in the bucket matches its manifest checksum; alert on missing, extra or modified objects';

    public function handle(AssetPackVerifier $verifier): int
    {
        try {
            $report = $verifier->verify();
        } catch (AssetPackException $e) {
            $this->error("Verify failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($report->ok()) {
            $this->info("assets:verify-pack — game/ matches its manifest ({$report->checked} assets).");

            return self::SUCCESS;
        }

        foreach ($report->missing as $key) {
            $this->error("missing: {$key}");
        }
        foreach ($report->modified as $key) {
            $this->error("modified: {$key}");
        }
        foreach ($report->extra as $key) {
            $this->warn("extra: {$key}");
        }

        return self::FAILURE;
    }
}
