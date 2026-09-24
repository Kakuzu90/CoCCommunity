<nav class="settings-nav" aria-label="Settings">
    <span class="settings-nav-group">
        <a href="{{ route('settings.profile.edit') }}" @if(request()->routeIs('settings.profile.*')) aria-current="page" @endif>Profile</a>
        <a href="{{ route('settings.privacy.edit') }}" @if(request()->routeIs('settings.privacy.*')) aria-current="page" @endif>Privacy</a>
        <a href="{{ route('settings.security.edit') }}" @if(request()->routeIs('settings.security.*')) aria-current="page" @endif>Security</a>
        <a href="{{ route('settings.sessions.index') }}" @if(request()->routeIs('settings.sessions.*')) aria-current="page" @endif>Sessions</a>
    </span>
    <span class="settings-nav-group settings-nav-aside">
        <a href="{{ route('profile.show', auth()->user()->username) }}">View profile</a>
        <a href="{{ route('settings.deletion.show') }}" @if(request()->routeIs('settings.deletion.*')) aria-current="page" @endif class="settings-nav-danger">Delete account</a>
    </span>
</nav>
