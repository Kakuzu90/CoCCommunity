<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Users\Services\PrivacyService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePrivacyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PrivacyController extends Controller
{
    public function edit(Request $request, PrivacyService $privacy): View
    {
        Gate::authorize('update-privacy');

        return view('settings.privacy', ['privacy' => $privacy->get($request->user())]);
    }

    public function update(UpdatePrivacyRequest $request, PrivacyService $privacy): RedirectResponse
    {
        Gate::authorize('update-privacy');
        $privacy->update($request->user(), $request->toData());

        return redirect()->route('settings.privacy.edit')->with('status', 'privacy-updated');
    }
}
