<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auth\Services\UserDirectory;
use App\Domain\Moderation\Services\SanctionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApplySanctionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Applying and lifting sanctions (specs/12 §6, specs/04 §2). Validate → authorize the exact ability
 * for this sanction → call the service, which re-checks the policy on the target and writes the
 * moderation, sanction and audit rows atomically. The controller holds no business logic and never
 * touches the User model; the target is resolved to an id by handle.
 */
class SanctionController extends Controller
{
    public function store(ApplySanctionRequest $request, string $username, UserDirectory $directory, SanctionService $sanctions): RedirectResponse
    {
        $targetId = $directory->resolveId($username);
        abort_if($targetId === null, 404);

        $type = $request->sanctionType();
        abort_if($type === null, 422);

        // Role-floor gate for this sanction (defence in depth); the service enforces the structural
        // "must outrank the target" rule on the policy and rolls back if it fails.
        Gate::authorize($type->ability());

        $result = $sanctions->apply(
            actorId: (int) $request->user()->getAuthIdentifier(),
            targetId: $targetId,
            type: $type,
            reason: $request->reasonCode(),
            publicReason: (string) $request->input('public_reason'),
            internalNote: $request->input('internal_note'),
            durationDays: $request->filled('duration_days') ? (int) $request->input('duration_days') : null,
        );

        return redirect()
            ->route('admin.users.show', $result->targetUsername)
            ->with('status', "{$type->label()} applied to {$result->targetUsername}.");
    }

    public function destroy(Request $request, string $username, UserDirectory $directory, SanctionService $sanctions): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $targetId = $directory->resolveId($username);
        abort_if($targetId === null, 404);

        Gate::authorize('lift-sanction');

        $result = $sanctions->lift(
            actorId: (int) $request->user()->getAuthIdentifier(),
            targetId: $targetId,
            reason: $validated['reason'],
        );

        return redirect()
            ->route('admin.users.show', $result->targetUsername)
            ->with('status', "Sanctions lifted for {$result->targetUsername}.");
    }
}
