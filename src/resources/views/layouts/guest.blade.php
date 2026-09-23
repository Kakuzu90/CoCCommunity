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
        <div class="min-h-screen relative flex flex-col items-center justify-center px-4 py-10 bg-ground overflow-hidden">
            <!-- ambient glow -->
            <div class="pointer-events-none absolute inset-0 -z-10">
                <div class="absolute -top-40 -left-32 w-[28rem] h-[28rem] rounded-full blur-3xl opacity-30" style="background: radial-gradient(circle, var(--primary), transparent 70%)"></div>
                <div class="absolute -bottom-40 -right-32 w-[28rem] h-[28rem] rounded-full blur-3xl opacity-20" style="background: radial-gradient(circle, var(--accent), transparent 70%)"></div>
            </div>

            <div class="absolute top-4 right-4">
                <x-theme-toggle />
            </div>

            <a href="/" wire:navigate class="flex items-center gap-2.5 mb-6">
                <span class="grid place-items-center w-10 h-10 rounded-xl bg-primary text-on-primary shadow-[0_4px_14px_rgba(108,59,245,.35)]">
                    <x-application-logo class="w-5 h-5" />
                </span>
                <span class="font-display font-extrabold text-xl text-content tracking-tight">Clash Commons</span>
            </a>

            <div class="w-full sm:max-w-md bg-surface border border-line shadow-sm rounded-2xl p-6 sm:p-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
