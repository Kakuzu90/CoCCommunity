@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · Clash Commons' : 'Clash Commons' }}</title>
    <meta name="description" content="{{ $description ?? 'Sign in to Clash Commons.' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
<a class="ui-skip" href="#content">Skip to content</a>
<x-ui.icons />

<div class="auth-shell">
    <header class="auth-header">
        <x-layout.brand />
    </header>

    <main id="content" class="auth-main">
        <div class="auth-card-wrap">
            {{ $slot }}
        </div>
    </main>

    <x-layout.footer />
</div>

@livewireScriptConfig
</body>
</html>
