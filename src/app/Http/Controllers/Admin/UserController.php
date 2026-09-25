<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auth\Data\AdminUserFilters;
use App\Domain\Auth\Enums\UserRole;
use App\Domain\Auth\Enums\UserStatus;
use App\Domain\Auth\Services\UserDirectory;
use App\Domain\Moderation\Enums\ReasonCode;
use App\Domain\Moderation\Enums\SanctionType;
use App\Domain\Moderation\Queries\UserSanctionHistoryQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * User list and detail (specs/12 §4, specs/04 §2 "View user list"). Validate/authorize → call the
 * directory service → render; the User model never reaches this layer. A missing handle 404s (IDOR:
 * users are addressed by username, so a wrong id can't be targeted).
 */
class UserController extends Controller
{
    public function index(Request $request, UserDirectory $directory): View
    {
        Gate::authorize('access-admin');

        $filters = AdminUserFilters::fromQuery($request->query());

        return view('admin.users.index', [
            'filters' => $filters,
            'users' => $directory->paginate(
                $filters,
                (int) config('accounts.admin.users_per_page'),
                (int) $request->user()->getAuthIdentifier(),
            )->withQueryString(),
            'roles' => UserRole::cases(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function show(string $username, UserDirectory $directory, UserSanctionHistoryQuery $sanctions): View
    {
        Gate::authorize('access-admin');

        $user = $directory->find($username);
        abort_if($user === null, 404);

        return view('admin.users.show', [
            'user' => $user,
            'sanctions' => $sanctions->forUser($user->id),
            'activeSanctions' => $sanctions->activeCount($user->id),
            'sanctionTypes' => SanctionType::all(),
            'reasonCodes' => ReasonCode::all(),
            'limits' => config('accounts.sanctions'),
        ]);
    }
}
