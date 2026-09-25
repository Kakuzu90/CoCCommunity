<?php

namespace App\Domain\Bases;

use App\Domain\Bases\Models\BaseLayout;
use App\Domain\Bases\Policies\BaseLayoutPolicy;
use App\Domain\Bases\Services\PublishBaseService;
use App\Domain\Media\Events\MediaReady;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class BasesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Relation::morphMap(['base_layout' => BaseLayout::class]);
        Gate::policy(BaseLayout::class, BaseLayoutPolicy::class);
        Gate::define('publish-base', fn (Authenticatable $user): bool => app(BaseLayoutPolicy::class)->create($user));
        RateLimiter::for('base-publish', fn (Request $request) => [
            Limit::perDay((int) config('bases.publish_day_limit'))->by((string) $request->user()?->getAuthIdentifier()),
            Limit::perMinutes((int) config('bases.publish_week_window_minutes'), (int) config('bases.publish_week_limit'))->by((string) $request->user()?->getAuthIdentifier()),
        ]);
        Event::listen(MediaReady::class, fn (MediaReady $event) => app(PublishBaseService::class)->mediaReady($event->mediaId));
    }
}
