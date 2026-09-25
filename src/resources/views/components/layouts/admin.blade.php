@props(['title' => null, 'heading' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' · Admin · Clash Commons' : 'Admin · Clash Commons' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a class="ui-skip" href="#admin-content">Skip to content</a>
<x-ui.icons />

<div class="adm">
    <div class="adm-shell">
        <aside class="adm-side">
            <div class="adm-brand">
                <span class="adm-brand-mark">CC</span>
                Admin
            </div>
            <x-layout.admin-nav />
            <a class="adm-side-back" href="{{ route('home') }}">
                <x-ui.icon name="arrow" size="18" class="adm-flip" />Back to site
            </a>
        </aside>

        <div class="adm-main">
            <header class="adm-topbar">
                <h1>{{ $heading ?? $title }}</h1>
                <div class="adm-topbar-aside">
                    <div class="adm-user">
                        <span class="adm-user-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->username, 0, 2)) }}</span>
                        <span class="adm-user-meta">
                            <span class="adm-user-name">{{ auth()->user()->username }}</span>
                            <span class="adm-user-role">{{ auth()->user()->role->label() }}</span>
                        </span>
                    </div>
                </div>
            </header>

            <main id="admin-content" class="adm-content">
                @if(session('status'))
                    <p class="adm-flash" role="status">{{ session('status') }}</p>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</div>
</body>
</html>
