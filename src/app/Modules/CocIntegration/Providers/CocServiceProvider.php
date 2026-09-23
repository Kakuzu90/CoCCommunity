<?php

declare(strict_types=1);

namespace App\Modules\CocIntegration\Providers;

use App\Modules\CocIntegration\Adapters\FakeClashClient;
use App\Modules\CocIntegration\Adapters\HttpClashClient;
use App\Modules\CocIntegration\Contracts\ClashClient;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class CocServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ClashClient::class, function (Application $app): ClashClient {
            /** @var Repository $config */
            $config = $app->make('config');

            if ($config->get('coc.driver') === 'fake') {
                return new FakeClashClient;
            }

            return new HttpClashClient(
                baseUrl: (string) $config->get('coc.base_url'),
                token: (string) $config->get('coc.token'),
                timeout: (int) $config->get('coc.timeout'),
                retries: (int) $config->get('coc.retries'),
                playerCacheTtl: (int) $config->get('coc.cache.player_ttl'),
            );
        });
    }
}
