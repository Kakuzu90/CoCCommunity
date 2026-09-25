<?php

namespace App\Http\Controllers\Web;

use App\Domain\PlayerAccounts\Services\AccountImageService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class AccountImageController extends Controller
{
    public function store(Request $request, string $ulid, AccountImageService $images): RedirectResponse
    {
        Gate::authorize('manage-own-coc-accounts');
        $validated = $request->validate([
            'media_ulid' => ['required', 'ulid'],
        ]);
        $images->add($request->user(), $ulid, $validated['media_ulid']);

        $request->session()->flash('status', 'image-added');

        return new RedirectResponse(route('accounts.show', $ulid));
    }

    public function destroy(Request $request, string $ulid, string $mediaUlid, AccountImageService $images): RedirectResponse
    {
        Gate::authorize('manage-own-coc-accounts');
        $images->remove($request->user(), $ulid, $mediaUlid);

        $request->session()->flash('status', 'image-removed');

        return new RedirectResponse(route('accounts.show', $ulid));
    }
}
