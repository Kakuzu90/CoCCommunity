<?php

namespace App\Http\Middleware;

use App\Domain\Auth\Services\AccountGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Write-gate 3 of 3 (specs/04 §3): publishing bases, recruiting and selling require at least one
 * verified in-game account. The attach flow arrives in Phase 2; until then this refuses with a 403.
 */
final class EnsureHasVerifiedCocAccount
{
    public function __construct(private readonly AccountGuard $guard) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        abort_unless(
            $this->guard->hasVerifiedCocAccount($user),
            403,
            'Verify an in-game account to publish, recruit or sell.',
        );

        return $next($request);
    }
}
