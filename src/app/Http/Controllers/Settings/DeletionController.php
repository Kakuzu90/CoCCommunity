<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Auth\Services\AccountDeletionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ConfirmDeletionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DeletionController extends Controller
{
    public function show(Request $request, AccountDeletionService $deletion): View
    {
        Gate::authorize('manage-own-sessions');

        return view('settings.deletion', ['deletion' => $deletion->get($request->user())]);
    }

    public function request(ConfirmDeletionRequest $request, AccountDeletionService $deletion): RedirectResponse
    {
        Gate::authorize('request-own-deletion');
        $deletion->request($request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Deletion scheduled. Sign in to cancel within 30 days.');
    }

    public function cancel(ConfirmDeletionRequest $request, AccountDeletionService $deletion): RedirectResponse
    {
        Gate::authorize('cancel-own-deletion');
        $deletion->cancel($request->user());
        $request->session()->regenerate();

        return redirect()->route('settings.deletion.show')->with('status', 'Deletion canceled.');
    }
}
