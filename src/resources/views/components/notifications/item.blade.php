@props(['notice'])
<li {{ $attributes->class('notification-item') }} data-unread="{{ $notice->unread ? 'true' : 'false' }}">
    <form method="post" action="{{ route('notifications.read', $notice->id) }}">
        @csrf
        <button type="submit" class="notification-open">
            <span class="notification-title">{{ $notice->title }}</span>
            <span class="notification-message">{{ $notice->message }}</span>
            <span class="notification-meta">
                @if($notice->unread)<span class="notification-unread">Unread</span>@else<span>Read</span>@endif
                <time datetime="{{ $notice->createdAt->toIso8601String() }}">{{ $notice->createdAt->diffForHumans() }}</time>
            </span>
        </button>
    </form>
</li>
