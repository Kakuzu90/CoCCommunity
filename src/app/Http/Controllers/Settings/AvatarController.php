<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Users\Services\ProfileService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateAvatarRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Attach or remove the profile avatar (specs/07, specs/10 §3). The bytes were uploaded directly to
 * storage and processed by the pipeline; here we only bind the processed media to the profile. The
 * MediaLibrary enforces ownership and collection on attach.
 */
class AvatarController extends Controller
{
    public function store(UpdateAvatarRequest $request, ProfileService $profiles): RedirectResponse
    {
        $profiles->setAvatar($request->user(), $request->validated('media_ulid'));

        return redirect()->route('settings.profile.edit')->with('status', 'avatar-updated');
    }

    public function destroy(Request $request, ProfileService $profiles): RedirectResponse
    {
        $profiles->removeAvatar($request->user());

        return redirect()->route('settings.profile.edit')->with('status', 'avatar-removed');
    }
}
