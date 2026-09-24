<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Services\AccountAuthService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Consumes a single-use reset token (60-minute expiry, config/auth) and sets a new password. The
 * service completes the reset: revoking every other session and cycling the remember token
 * (specs/11: password resets invalidate all sessions).
 */
final class NewPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function store(Request $request, AccountAuthService $accounts): RedirectResponse
    {
        $rule = PasswordRule::min((int) config('accounts.password.min'));
        if (config('accounts.password.check_compromised')) {
            $rule = $rule->uncompromised();
        }

        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', $rule],
        ]);

        $status = Password::reset(
            [
                'email' => $request->string('email')->lower()->value(),
                'password' => $request->string('password')->value(),
                'password_confirmation' => (string) $request->input('password_confirmation'),
                'token' => (string) $request->input('token'),
            ],
            fn (Authenticatable $user, string $password) => $accounts->completePasswordReset($user, $password),
        );

        if ($status === Password::PasswordReset) {
            return redirect()->route('login')->with('status', 'Your password has been reset. Please sign in.');
        }

        // Generic failure — invalid or expired token, without confirming which (specs/11).
        return back()->withInput($request->only('email'))->withErrors(['email' => 'This password reset link is invalid or has expired.']);
    }
}
