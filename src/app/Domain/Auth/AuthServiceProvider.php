<?php

namespace App\Domain\Auth;

use App\Domain\Auth\Enums\Ability;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerAuthorization();
        $this->registerRateLimiters();
    }

    /**
     * Policies and Gates are the only source of authorization truth (specs/04 §3, specs/11). Each
     * matrix ability becomes a role-gated Gate whose threshold comes from the `Ability` enum, so the
     * gates cannot drift from the matrix test. `Gate::before` grants super admin everything except
     * the one permanently-denied ability, impersonation (a locked decision).
     */
    private function registerAuthorization(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        Gate::before(function (User $user, string $ability): ?bool {
            if ($ability === Ability::Impersonate->value) {
                return null; // Falls through to the ability gate, which denies everyone.
            }

            return $user->role->isSuperAdmin() ? true : null;
        });

        foreach (Ability::all() as $ability) {
            Gate::define(
                $ability->value,
                fn (User $user): bool => $user->status->canAuthenticate() && $ability->grantedTo($user->role),
            );
        }
    }

    /**
     * Named limiters for the auth surfaces (specs/04 §4). All go through the Cache facade, so they
     * move to Redis unchanged. Keys combine ip + email (or the user) to blunt both spray and
     * targeted attacks without locking a whole IP out on a shared network.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by($this->loginKey($request)),
            Limit::perHour(20)->by($this->loginKey($request)),
        ]);

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(3)->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perHour(3)
            ->by(($request->ip() ?? '').'|'.strtolower((string) $request->input('email'))));

        RateLimiter::for('verify-email-resend', fn (Request $request) => Limit::perHour(3)
            ->by((string) (optional($request->user())->getAuthIdentifier() ?? $request->ip())));

        RateLimiter::for('username-check', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }

    private function loginKey(Request $request): string
    {
        return ($request->ip() ?? '').'|'.strtolower((string) $request->input('email'));
    }
}
