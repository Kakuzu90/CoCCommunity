<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
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

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen relative overflow-hidden">
            <!-- ambient glow -->
            <div class="pointer-events-none absolute inset-0 -z-10">
                <div class="absolute -top-32 -left-24 w-96 h-96 rounded-full blur-3xl opacity-40" style="background: radial-gradient(circle, var(--primary), transparent 70%)"></div>
                <div class="absolute -bottom-40 -right-24 w-[28rem] h-[28rem] rounded-full blur-3xl opacity-30" style="background: radial-gradient(circle, var(--accent), transparent 70%)"></div>
            </div>

            <header class="max-w-6xl mx-auto px-6 py-6 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="grid place-items-center w-9 h-9 rounded-xl bg-primary text-on-primary shadow-[0_4px_14px_rgba(108,59,245,.35)]">
                        <x-application-logo class="w-5 h-5" />
                    </span>
                    <span class="font-display font-extrabold text-lg text-content tracking-tight">Clash Commons</span>
                </div>
                @if (Route::has('login'))
                    <nav class="flex items-center gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="font-display font-semibold text-sm text-content hover:text-primary transition">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="font-display font-semibold text-sm text-content-muted hover:text-content transition">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 rounded-xl font-display font-semibold text-sm text-on-primary bg-primary hover:bg-primary-hi shadow-[0_4px_14px_rgba(108,59,245,.35)] transition">Get started</a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </header>

            <main class="max-w-6xl mx-auto px-6 pt-10 sm:pt-20 pb-24">
                <div class="max-w-2xl">
                    <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-primary bg-primary-soft px-3 py-1 rounded-full">For Clash of Clans players</span>
                    <h1 class="mt-5 font-display font-extrabold text-4xl sm:text-6xl leading-[1.05] text-content tracking-tight text-balance">
                        Your verified home for bases, clans &amp; the community.
                    </h1>
                    <p class="mt-5 text-lg text-content-muted max-w-xl">
                        Link your accounts with in-game verification, share war and farming layouts, and find the clan that fits — all in one place.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center px-6 py-3 rounded-xl font-display font-semibold text-on-primary bg-primary hover:bg-primary-hi shadow-[0_4px_14px_rgba(108,59,245,.35)] transition">Open dashboard</a>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex items-center px-6 py-3 rounded-xl font-display font-semibold text-on-primary bg-primary hover:bg-primary-hi shadow-[0_4px_14px_rgba(108,59,245,.35)] transition">Create account</a>
                            <a href="{{ route('login') }}" class="inline-flex items-center px-6 py-3 rounded-xl font-display font-semibold text-content bg-surface border border-line-strong hover:bg-surface-2 transition">Log in</a>
                        @endauth
                    </div>

                    <div class="mt-14 grid gap-4 sm:grid-cols-3">
                        @foreach ([
                            ['Verified accounts', 'Prove ownership with the in-game API token — one owner per tag.'],
                            ['Base sharing', 'Post layouts with screenshots, likes, and copy links.'],
                            ['Recruitment', 'Find a clan, or recruit the right players.'],
                        ] as $feature)
                            <div class="rounded-2xl bg-surface border border-line p-4 shadow-sm">
                                <h3 class="font-display font-bold text-content">{{ $feature[0] }}</h3>
                                <p class="mt-1 text-sm text-content-muted">{{ $feature[1] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
