<?php

namespace App\Http\Controllers\Admin;

use App\Domain\PlayerAccounts\Enums\DisputeStatus;
use App\Domain\PlayerAccounts\Queries\DisputeQuery;
use App\Http\Controllers\Controller;
use App\Livewire\Admin\ResolveDispute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The admin disputes queue and detail (specs/13 §5 step 4, specs/04 §2 "Resolve ownership dispute").
 * Read surfaces here; the resolve actions live in the {@see ResolveDispute} panel so
 * the ownership move stays a single audited transaction. Gated above the admin-area middleware by the
 * dedicated `resolve-disputes` ability, and addressed by the dispute's opaque ulid, never its id.
 */
class DisputeController extends Controller
{
    public function index(Request $request, DisputeQuery $disputes): View
    {
        Gate::authorize('resolve-disputes');

        $status = DisputeStatus::tryFrom((string) $request->query('status'));

        return view('admin.disputes.index', [
            'status' => $status,
            'disputes' => $disputes->queue($status, (int) config('accounts.admin.audit_per_page', 20))
                ->withQueryString(),
        ]);
    }

    public function show(string $ulid, DisputeQuery $disputes): View
    {
        Gate::authorize('resolve-disputes');

        $dispute = $disputes->findByUlid($ulid);
        abort_if($dispute === null, 404);

        return view('admin.disputes.show', ['dispute' => $dispute]);
    }
}
