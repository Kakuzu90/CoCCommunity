<div class="notification-bell" wire:poll.60s.visible x-data>
    <details x-ref="panel" @click.outside="$refs.panel.open = false" @keydown.escape.stop="$refs.panel.open = false; $refs.trigger.focus()">
        <summary class="app-iconbtn" x-ref="trigger" aria-label="{{ $failed ? 'Notifications unavailable' : 'Notifications, '.$unread.' unread' }}">
            <x-ui.icon name="bell" size="24" />
            @if($unread > 0)<span class="notification-count" aria-hidden="true">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
        </summary>
        <section class="notification-popover" aria-label="Recent notifications">
            <div class="notification-heading"><h2>Notifications</h2><a href="{{ route('notifications.index') }}">View all</a></div>
            @unless($failed)<p class="sr-only" role="status">{{ $unread }} unread notifications</p>@endunless
            <p wire:loading role="status">Updating notifications…</p>
            <p wire:offline role="alert">You're offline. Reconnect to update notifications.</p>
            @if($failed)
                <p role="alert">Notifications could not be loaded.</p>
                <x-ui.button wire:click="$refresh" wire:loading.attr="disabled">Try again</x-ui.button>
            @elseif($notifications->isEmpty())
                <p class="notification-empty">You're all caught up.</p>
            @else
                <ul class="notification-list">
                    @foreach($notifications as $notice)
                        <x-notifications.item :notice="$notice" wire:key="notice-{{ $notice->id }}" />
                    @endforeach
                </ul>
                <form action="{{ route('notifications.read-all') }}" method="post">
                    @csrf
                    <x-ui.button type="submit" variant="ghost" :disabled="$unread === 0">Mark all read</x-ui.button>
                </form>
            @endif
        </section>
    </details>
</div>
