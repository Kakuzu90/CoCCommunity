@props(['title' => null, 'description' => null, 'robots' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · Clash Commons' : 'Clash Commons — the Clash of Clans community platform' }}</title>
    <meta name="description" content="{{ $description ?? 'Verify your Clash of Clans account, share base layouts people can trust, and find a clan that fits.' }}">
    @if($robots)<meta name="robots" content="{{ $robots }}">@endif
    <link rel="preload" href="{{ Vite::asset('node_modules/@fontsource/lilita-one/files/lilita-one-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
<a class="ui-skip" href="#content">Skip to content</a>
<x-ui.icons />

<div class="app-shell">
    <x-layout.top-bar />

    <div class="app-body">
        <x-layout.sidebar />
        <main id="content" class="app-main">
            <div class="app-container">
                {{ $slot }}
            </div>
        </main>
    </div>

    <x-layout.footer />
</div>

<x-layout.bottom-nav />

@livewireScriptConfig
</body>
</html>
