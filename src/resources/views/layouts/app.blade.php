<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Clash Commons') }}</title>

        <script>
            window.__theme = {
                get() { try { return localStorage.getItem('theme'); } catch (e) { return null; } },
                apply() {
                    var t = this.get(), el = document.documentElement;
                    if (t) { el.setAttribute('data-theme', t); } else { el.removeAttribute('data-theme'); }
                },
                set(t) { try { localStorage.setItem('theme', t); } catch (e) {} this.apply(); window.dispatchEvent(new CustomEvent('theme-changed', { detail: t })); },
                toggle() {
                    var cur = this.get();
                    var isDark = cur ? cur === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                    this.set(isDark ? 'light' : 'dark');
                }
            };
            window.__theme.apply();
            document.addEventListener('livewire:navigated', function () { window.__theme.apply(); });
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-ground">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-surface border-b border-line">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
