<?php

namespace App\Console\Commands;

use App\Domain\GameAssets\Exceptions\AssetPackException;
use App\Domain\GameAssets\Services\AssetPackVerifier;
use Illuminate\Console\Command;

/** Weekly integrity audit (specs/10 §9): the bucket must still match the committed manifest. */
class AssetsVerifyPack extends Command
{
    protected $signature = 'assets:verify-pack {--pack= : Pack version to audit (defaults to the active version)}';

    protected $description = 'Verify every game asset in the bucket matches its manifest checksum; alert on missing, extra or modified objects';

    public function handle(AssetPackVerifier $verifier): int
    {
        $version = (int) ($this->option('pack') ?? config('assets.pack_version'));

        try {
            $report = $verifier->verify($version);
        } catch (AssetPackException $e) {
            $this->error("Verify failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($report->ok()) {
            if ($report->checked === 0) {
                $this->info("assets:verify-pack — game/{$version}/ is an empty placeholder pack (0 game assets published).");

                return self::SUCCESS;
            }

            $this->info("assets:verify-pack — game/{$version}/ matches its manifest ({$report->checked} assets).");

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
