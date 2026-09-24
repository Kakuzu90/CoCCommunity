<?php

namespace App\Http\Middleware;

use App\Domain\Auth\Services\AccountGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Write-gate 2 of 3 (specs/04 §3): blocks writes from restricted, suspended, banned and
 * pending-deletion accounts. Only `active` passes; the refusal message names the reason.
 */
final class EnsureAccountIsActive
{
    public function __construct(private readonly AccountGuard $guard) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        abort_unless($this->guard->canWrite($user), 403, $this->guard->writeBlockedMessage($user));

        return $next($request);
    }
}
