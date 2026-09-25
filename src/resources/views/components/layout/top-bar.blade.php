<header class="app-topbar">
    <x-layout.brand />

    {{-- Tablet (768–1023px): primary nav sits inline in the bar. --}}
    <x-layout.primary-nav variant="top" class="app-topbar-nav" aria-label="Primary" />

    <div class="app-topbar-spacer"></div>

    {{-- Desktop: prominent search with a "/" shortcut. --}}
    <form class="app-search" role="search" action="{{ route('search') }}" method="get"
          x-data @keydown.window.slash="if (!/^(?:INPUT|TEXTAREA|SELECT)$/.test($event.target.tagName) && !$event.target.isContentEditable) { $event.preventDefault(); $refs.q.focus(); }">
        <x-ui.icon name="search" size="20" class="app-search-icon" />
        <label class="sr-only" for="topbar-search">Search bases, players and clans</label>
        <input id="topbar-search" name="q" type="search" x-ref="q" class="app-search-input"
               placeholder="Search bases, players, clans" autocomplete="off">
    </form>

    <div class="app-topbar-actions">
        {{-- Mobile: search collapses to an icon that opens the search page. --}}
        <a href="{{ route('search') }}" class="app-iconbtn app-search-trigger" aria-label="Search">
            <x-ui.icon name="search" size="24" />
        </a>

        @auth
            @can('manage-own-notifications')<livewire:components.notification-bell />@endcan
            {{-- Public profile (/u/{username}) arrives with the public-profile task; role-gated staff links join this menu. --}}
            <x-ui.dropdown label="Account">
                <a href="{{ route('settings.profile.edit') }}" class="ui-menu-item" role="menuitem" tabindex="-1">Edit profile</a>
                <a href="{{ route('accounts.index') }}" class="ui-menu-item" role="menuitem" tabindex="-1">My accounts</a>
                <a href="{{ route('settings.profile.edit') }}" class="ui-menu-item" role="menuitem" tabindex="-1">Settings</a>
                @can('access-admin')
                    <a href="{{ route('admin.dashboard') }}" class="ui-menu-item" role="menuitem" tabindex="-1">Admin</a>
                @endcan
                <form method="POST" action="{{ route('logout') }}" role="none">
                    @csrf
                    <button type="submit" class="ui-menu-item" role="menuitem" tabindex="-1">Log out</button>
                </form>
            </x-ui.dropdown>
        @else
            <a href="{{ route('login') }}" class="ui-button app-auth-login" data-variant="ghost" data-size="sm">Log in</a>
            <a href="{{ route('register') }}" class="ui-button" data-size="sm">Sign up</a>
        @endauth
    </div>
</header>
