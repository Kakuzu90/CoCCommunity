<?php

namespace App\Http\Controllers\Web;

use App\Domain\PlayerAccounts\Data\ProfileAccounts;
use App\Domain\PlayerAccounts\Queries\AccountDetailQuery;
use App\Domain\Users\Services\PublicProfileReadModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicProfileController extends Controller
{
    public function __invoke(Request $request, string $username, PublicProfileReadModel $profiles, AccountDetailQuery $accounts): View
    {
        $result = $profiles->find($username, $request->user()?->getAuthIdentifier());
        abort_if($result === null, 404);

        $viewerId = $request->user()?->getAuthIdentifier();
        $result['accounts'] = $result['state'] === 'visible'
            ? $accounts->forProfile($result['profile']->profile->userId, $viewerId)
            : ProfileAccounts::empty();
        $result['isOwner'] = $result['state'] === 'visible' && $viewerId === $result['profile']->profile->userId;
        $result['basePublishing'] = (bool) config('features.base_publishing');

        return view('pages.profile', $result);
    }
}
