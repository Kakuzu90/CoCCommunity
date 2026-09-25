<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\Queries\AuditLogQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Audit log viewer (specs/04 §2 "View audit log" — admin and above; specs/12 AuditTrailList). Gated by
 * the dedicated `view-audit-log` ability, above the general admin-area gate. Read-only.
 */
class AuditLogController extends Controller
{
    public function index(Request $request, AuditLogQuery $audit): View
    {
        Gate::authorize('view-audit-log');

        $filters = [
            'action' => $request->query('action'),
            'actor' => $request->query('actor'),
        ];

        return view('admin.logs.index', [
            'filters' => $filters,
            'entries' => $audit->paginate($filters, (int) config('accounts.admin.audit_per_page'))
                ->withQueryString(),
            'actionOptions' => $audit->actionOptions(),
        ]);
    }
}
