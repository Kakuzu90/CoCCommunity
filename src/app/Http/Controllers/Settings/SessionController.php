<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Auth\Services\SessionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(Request $request, SessionService $sessions): View
    {
        Gate::authorize('manage-own-sessions');

        return view('settings.sessions', [
            'sessions' => $sessions->list($request->user(), $request->session()->getId()),
        ]);
    }

    public function destroy(Request $request, string $sessionId, SessionService $sessions): RedirectResponse
    {
        Gate::authorize('manage-own-sessions');
        abort_unless($sessions->revoke($request->user(), $sessionId, $request->session()->getId()), 404);

        return redirect()->route('settings.sessions.index')->with('status', 'session-revoked');
    }

    public function destroyOthers(Request $request, SessionService $sessions): RedirectResponse
    {
        Gate::authorize('manage-own-sessions');
        $sessions->revokeOthers($request->user(), $request->session()->getId());

        return redirect()->route('settings.sessions.index')->with('status', 'sessions-revoked');
    }

    public function destroyAll(Request $request, SessionService $sessions): RedirectResponse
    {
        Gate::authorize('manage-own-sessions');
        $sessions->revokeAll($request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'All devices were signed out.');
    }
}
