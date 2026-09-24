<?php

namespace App\Http\Middleware;

use App\Domain\Auth\Services\AccountGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Write-gate 1 of 3 (specs/04 §3): every write requires a verified email. Guests are sent to log in;
 * signed-in but unverified users are sent to the verification notice rather than shown a raw 403.
 */
final class EnsureEmailIsVerified
{
    public function __construct(private readonly AccountGuard $guard) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if (! $this->guard->hasVerifiedEmail($user)) {
            return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
