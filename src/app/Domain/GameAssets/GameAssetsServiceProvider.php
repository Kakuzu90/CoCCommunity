<?php

namespace App\Domain\GameAssets;

use App\Domain\GameAssets\Contracts\GameAssetResolver;
use App\Domain\GameAssets\Services\GameAssetCatalogue;
use App\Domain\GameAssets\Services\ManifestGameAssetResolver;
use Illuminate\Support\ServiceProvider;

final class GameAssetsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton so the committed manifest is read once per request, not per asset — the
        // resolver is the one seam every game asset is referenced through (specs/18 §2.3).
        $this->app->singleton(GameAssetResolver::class, ManifestGameAssetResolver::class);
        $this->app->singleton(GameAssetCatalogue::class);
    }
}
