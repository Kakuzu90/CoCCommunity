<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Services\AccountAuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Session login/logout (specs/04 §4). Rate limiting is the `login` limiter applied as route
 * middleware; the session id is regenerated on login and logout to defeat fixation, and a blocked
 * status is refused after the credential check so the check itself stays constant-time.
 */
final class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AccountAuthService $accounts): RedirectResponse
    {
        $credentials = [
            'email' => $request->string('email')->lower()->value(),
            'password' => $request->string('password')->value(),
        ];

        // One generic failure for unknown-email and wrong-password alike (specs/11 enumeration).
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $user = $request->user();

        // Suspended and banned accounts may hold valid credentials but cannot start a session.
        if ($user !== null && ($locked = $accounts->lockReason($user)) !== null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withInput($request->only('email'))->withErrors(['email' => $locked]);
        }

        $request->session()->regenerate();
        if ($user !== null) {
            $accounts->recordLogin($user, $request->ip());
        }

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        // Auth::logout cycles the remember token, revoking remember-me cookies (specs/11).
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
