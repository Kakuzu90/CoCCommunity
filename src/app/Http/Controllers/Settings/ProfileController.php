<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Users\Services\ProfileService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Edit-own-profile surface (specs/07). Thin: validate → call the Users service → render. Profile
 * data is read and written only through ProfileService, never the Profile model.
 */
class ProfileController extends Controller
{
    public function edit(Request $request, ProfileService $profiles): View
    {
        return view('settings.profile', [
            'profile' => $profiles->get($request->user()),
        ]);
    }

    public function update(UpdateProfileRequest $request, ProfileService $profiles): RedirectResponse
    {
        $profiles->update($request->user(), $request->toInput());

        return redirect()->route('settings.profile.edit')->with('status', 'profile-updated');
    }
}
