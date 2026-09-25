<?php

namespace App\Http\Controllers\Web;

use App\Domain\Notifications\Services\NotificationReadModel;
use App\Domain\Notifications\Services\ReadNotifications;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NotificationController extends Controller
{
    public function index(Request $request, NotificationReadModel $inbox): View
    {
        $input = $request->validate(['category' => ['nullable', 'in:all,security,moderation']]);
        $category = $input['category'] ?? 'all';

        return view('notifications.index', [
            'notifications' => $inbox->paginate((int) $request->user()?->getAuthIdentifier(), $category),
            'unread' => $inbox->unread((int) $request->user()?->getAuthIdentifier()),
            'category' => $category,
        ]);
    }

    public function read(Request $request, string $id, ReadNotifications $read): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);
        $notice = $read->one($user, $id);

        return redirect($notice->target ?? route('notifications.index'))->with('status',
            $notice->target === null ? $notice->message : 'Notification marked as read.');
    }

    public function readAll(Request $request, ReadNotifications $read): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);
        $read->all($user);

        return redirect()->route('notifications.index')->with('status', 'All notifications marked as read.');
    }
}
