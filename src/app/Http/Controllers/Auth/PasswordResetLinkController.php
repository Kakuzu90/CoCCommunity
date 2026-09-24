<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Rules\TurnstileRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

final class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'cf-turnstile-response' => [new TurnstileRule($request->ip())],
        ]);

        // Fire-and-forget: the broker's result is never surfaced, so the form cannot confirm
        // whether an account exists (specs/11). Reset emails are our branded notification.
        Password::sendResetLink(['email' => $request->string('email')->lower()->value()]);

        return back()->with('status', "If an account exists for that email, we've sent a reset link.");
    }
}
