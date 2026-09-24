<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Services\EmailVerifier;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Email verification (specs/04 §4). The verify link is a signed URL, so it is honoured even for a
 * signed-out visitor — the signature plus the email hash are the proof, not the session. Resending
 * requires an authenticated, still-unverified user and is rate limited.
 */
final class EmailVerificationController extends Controller
{
    /** Shown to a logged-in, unverified user who hits a gated action. */
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()?->hasVerifiedEmail()
            ? redirect()->intended(route('home'))
            : view('auth.verify-email');
    }

    public function verify(Request $request, string $id, string $hash, EmailVerifier $verifier): RedirectResponse
    {
        abort_unless($verifier->verify($id, $hash), Response::HTTP_FORBIDDEN);

        return redirect()->route('login')->with('status', 'Your email is verified. You can now sign in.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user === null || $user->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'A fresh verification link is on its way.');
    }
}
