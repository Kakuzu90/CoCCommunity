<?php

namespace App\Console\Commands;

use App\Domain\GameAssets\Exceptions\AssetPackException;
use App\Domain\GameAssets\Services\AssetPackPublisher;
use Illuminate\Console\Command;

/**
 * Runbook, not a feature (specs/10 §11.2): staff assemble a byte-exact pack + manifest locally,
 * then publish it to game/{version}/. Activation stays a separate config change.
 */
class AssetsPublishPack extends Command
{
    protected $signature = 'assets:publish-pack {path : Directory holding the pack and its manifest.json} {--pack= : Pack version to publish under game/{version}/}';

    protected $description = 'Upload a curated game asset pack to the game/ prefix, byte-for-byte, verified against its manifest';

    public function handle(AssetPackPublisher $publisher): int
    {
        $version = (int) ($this->option('pack') ?? config('assets.pack_version'));

        try {
            $report = $publisher->publish((string) $this->argument('path'), $version);
        } catch (AssetPackException $e) {
            $this->error("Publish aborted: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Published {$report->count()} assets to game/{$version}/. Activate with config('assets.pack_version') = {$version}.");

        return self::SUCCESS;
    }
}
