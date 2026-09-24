<?php

namespace App\Domain\Media;

use App\Domain\Media\Contracts\ImageProcessor;
use App\Domain\Media\Contracts\MediaStorage;
use App\Domain\Media\Storage\InterventionImageProcessor;
use App\Domain\Media\Storage\S3MediaStorage;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The only bindings that know a storage provider or image library exists.
        $this->app->bind(MediaStorage::class, S3MediaStorage::class);
        $this->app->bind(ImageProcessor::class, InterventionImageProcessor::class);
    }

    public function boot(): void
    {
        // Named limiter (specs/04): 30 upload intents per hour, keyed by the authenticated user
        // (the route is behind `auth`). Uses the Cache facade, so it moves to Redis unchanged.
        RateLimiter::for('upload-intent', fn (Request $request) => Limit::perHour(30)
            ->by((string) (Auth::id() ?? $request->ip())));
    }
}
