<?php

namespace App\Livewire\Components;

use App\Domain\Notifications\Services\NotificationReadModel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

final class NotificationBell extends Component
{
    public function render(): View
    {
        abort_unless(Auth::check(), 401);
        Gate::authorize('manage-own-notifications');
        $inbox = app(NotificationReadModel::class);

        try {
            return view('livewire.components.notification-bell', [
                'unread' => $inbox->unread((int) Auth::id()),
                'notifications' => $inbox->recent((int) Auth::id()),
                'failed' => false,
            ]);
        } catch (QueryException $exception) {
            report($exception);

            return view('livewire.components.notification-bell', ['unread' => 0, 'notifications' => collect(), 'failed' => true]);
        }
    }
}
