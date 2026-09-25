<x-layouts.app title="Notifications" robots="noindex,nofollow">
    <section class="notification-page" aria-labelledby="notification-heading">
        <div class="notification-heading">
            <h1 id="notification-heading">Notifications</h1>
            <form action="{{ route('notifications.read-all') }}" method="post">
                @csrf
                <x-ui.button type="submit" variant="secondary" :disabled="$unread === 0">Mark all read</x-ui.button>
            </form>
        </div>
        @if(session('status'))<p role="status" class="ui-alert">{{ session('status') }}</p>@endif
        <nav class="notification-filters" aria-label="Notification category">
            @foreach(['all' => 'All', 'security' => 'Security', 'moderation' => 'Moderation'] as $key => $label)
                <a href="{{ route('notifications.index', ['category' => $key]) }}" @if($category === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        @if($notifications->isEmpty())
            <x-ui.empty-state title="You're all caught up" body="Account updates will appear here.">
                <x-slot:illustration><x-ui.icon name="bell" size="24" /></x-slot:illustration>
            </x-ui.empty-state>
        @else
            <ul class="notification-list">
                @foreach($notifications as $notice)
                    <x-notifications.item :notice="$notice" />
                @endforeach
            </ul>
            {{ $notifications->withQueryString()->links() }}
        @endif
    </section>
</x-layouts.app>
