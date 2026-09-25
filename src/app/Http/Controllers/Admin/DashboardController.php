<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\Queries\AuditLogQuery;
use App\Domain\Auth\Services\UserDirectory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Admin landing (specs/18 §6). Thin: authorize the area, then render headline counts and the most
 * recent audit entries. The moderation-case KPIs of specs/12 §11 arrive with the Phase 3 queue —
 * this v1 dashboard covers what the user-management and audit surfaces can answer today.
 */
class DashboardController extends Controller
{
    public function __invoke(UserDirectory $users, AuditLogQuery $audit): View
    {
        Gate::authorize('access-admin');

        return view('admin.dashboard', [
            'counts' => $users->counts(),
            'recent' => $audit->paginate(perPage: 8)->items(),
        ]);
    }
}
