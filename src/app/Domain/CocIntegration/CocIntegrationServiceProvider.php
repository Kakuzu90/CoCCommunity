<?php

namespace App\Domain\CocIntegration;

use App\Domain\CocIntegration\Contracts\CocApiClient;
use App\Domain\CocIntegration\Http\CachedCocApiClient;
use App\Domain\CocIntegration\Http\HttpCocApiClient;
use App\Domain\CocIntegration\Http\ThrottledCocApiClient;
use App\Domain\CocIntegration\KeyManagement\CocKeyPool;
use App\Domain\CocIntegration\Resilience\CircuitBreaker;
use App\Domain\CocIntegration\Resilience\RequestLogger;
use App\Domain\CocIntegration\Testing\FakeCocApiClient;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\ServiceProvider;

final class CocIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CocKeyPool::class, fn ($app) => new CocKeyPool(
            (array) config('coc.tokens'),
            $app->make(Cache::class),
        ));

        $this->app->singleton(CircuitBreaker::class, fn ($app) => new CircuitBreaker($app->make(Cache::class)));
        $this->app->singleton(RequestLogger::class);

        // The client stack: Cached( Throttled( Http ) ), caching outermost so a hit costs no rate
        // budget (specs/09 §1). The fake driver short-circuits the whole stack for tests and local dev.
        $this->app->singleton(CocApiClient::class, function ($app): CocApiClient {
            if (config('coc.driver') === 'fake') {
                return new FakeCocApiClient;
            }

            $http = new HttpCocApiClient($app->make(Http::class), $app->make(CocKeyPool::class));
            $throttled = new ThrottledCocApiClient($http, $app->make(CircuitBreaker::class), $app->make(RequestLogger::class));

            return new CachedCocApiClient($throttled, $app->make(Cache::class));
        });
    }
}
