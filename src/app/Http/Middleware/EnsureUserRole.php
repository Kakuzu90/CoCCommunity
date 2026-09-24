<?php

namespace App\Http\Middleware;

use App\Domain\Auth\Enums\UserRole;
use App\Domain\Auth\Services\AccountGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role floor for staff areas (specs/04 §3, specs/11): admin/moderation routes sit behind this *and*
 * a Gate check in the component — defence in depth, the middleware is not the sole check. Usage:
 * `->middleware('role:moderator')`. An unknown role name is a programming error and 500s.
 */
final class EnsureUserRole
{
    public function __construct(private readonly AccountGuard $guard) {}

    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        abort_unless($this->guard->roleAtLeast($user, UserRole::from($role)), 403);

        return $next($request);
    }
}
