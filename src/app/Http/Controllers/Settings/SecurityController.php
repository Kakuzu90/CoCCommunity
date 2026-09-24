<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Auth\Services\CredentialService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ChangeEmailRequest;
use App\Http\Requests\Settings\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('manage-own-credentials');

        return view('settings.security');
    }

    public function updatePassword(ChangePasswordRequest $request, CredentialService $credentials): RedirectResponse
    {
        Gate::authorize('manage-own-credentials');
        $credentials->changePassword($request->user(), $request->validated('password'), $request->session()->getId());
        $request->session()->regenerate();

        return redirect()->route('settings.security.edit')->with('status', 'password-updated');
    }

    public function updateEmail(ChangeEmailRequest $request, CredentialService $credentials): RedirectResponse
    {
        Gate::authorize('manage-own-credentials');
        $credentials->changeEmail($request->user(), $request->validated('email'), $request->session()->getId());
        $request->session()->regenerate();

        return redirect()->route('verification.notice')->with('status', 'Verify your new email address.');
    }
}
