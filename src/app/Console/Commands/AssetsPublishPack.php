<?php

namespace App\Console\Commands;

use App\Domain\GameAssets\Exceptions\AssetPackException;
use App\Domain\GameAssets\Services\AssetPackPublisher;
use Illuminate\Console\Command;

/**
 * Runbook, not a feature (specs/10 §11.2): staff assemble a byte-exact pack + manifest locally,
 * then publish it to game/.
 */
class AssetsPublishPack extends Command
{
    protected $signature = 'assets:publish-pack {path? : Directory holding the pack and its manifest.json (defaults to config assets.pack_path)}';

    protected $description = 'Upload the curated game asset pack to the game/ prefix, byte-for-byte, verified against its manifest';

    public function handle(AssetPackPublisher $publisher): int
    {
        try {
            $report = $publisher->publish((string) ($this->argument('path') ?? config('assets.pack_path')));
        } catch (AssetPackException $e) {
            $this->error("Publish aborted: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Published {$report->count()} assets to game/.");

        return self::SUCCESS;
    }
}
