<?php

namespace App\Http\Controllers\Web;

use App\Domain\Bases\Services\BaseSubmissionQuery;
use App\Domain\Bases\Services\PublishBaseService;
use App\Domain\PlayerAccounts\Services\VerifiedAccountLookup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bases\PublishBaseRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class BaseComposerController extends Controller
{
    public function create(Request $request, VerifiedAccountLookup $accounts): View
    {
        return view('bases.create', [
            'accounts' => $accounts->optionsFor((int) $request->user()->getAuthIdentifier()),
        ]);
    }

    public function store(PublishBaseRequest $request, PublishBaseService $publisher): RedirectResponse
    {
        $base = $publisher->publish($request->user(), $request->publishData());

        return redirect()->route('bases.submitted', $base->ulid);
    }

    public function submitted(Request $request, string $ulid, BaseSubmissionQuery $submissions): View
    {
        return view('bases.submitted', [
            'base' => $submissions->own((int) $request->user()->getAuthIdentifier(), $ulid),
        ]);
    }
}
