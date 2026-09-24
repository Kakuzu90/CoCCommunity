{{-- Mobile (<768px) bottom tab bar: Home · Bases · Recruit · Search · Profile (specs/18 §5). --}}
<nav class="app-bottomnav" aria-label="Primary">
    @foreach (config('navigation.primary') as $item)
        <a href="{{ route($item['route']) }}" class="app-bottomnav-link"
           @if (request()->routeIs($item['active'])) aria-current="page" @endif>
            <x-ui.icon :name="$item['icon']" size="24" />
            <span class="app-bottomnav-label">{{ $item['label'] }}</span>
        </a>
    @endforeach
    @auth
        {{-- Phase 1: point at the signed-in user's public profile. --}}
        <a href="{{ route('home') }}" class="app-bottomnav-link">
            <x-ui.icon name="user" size="24" />
            <span class="app-bottomnav-label">Profile</span>
        </a>
    @else
        <a href="{{ route('login') }}" class="app-bottomnav-link"
           @if (request()->routeIs('login')) aria-current="page" @endif>
            <x-ui.icon name="user" size="24" />
            <span class="app-bottomnav-label">Log in</span>
        </a>
    @endauth
</nav>
