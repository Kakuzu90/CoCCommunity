<x-app-layout>
    @php($user = auth()->user())
    @php($initials = \Illuminate\Support\Str::of($user->name)->explode(' ')->take(2)->map(fn ($p) => \Illuminate\Support\Str::substr($p, 0, 1))->implode('') ?: 'C')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">

        <div class="overflow-hidden rounded-2xl border border-line bg-surface shadow-sm">

            {{-- Crest banner --}}
            <div class="relative border-b border-line bg-surface-2 px-6 py-7 sm:px-8 sm:py-8"
                 style="background-image: repeating-linear-gradient(135deg, rgba(240,168,31,0.05) 0 2px, transparent 2px 11px);">
                <div class="absolute inset-x-0 top-0 h-[3px] bg-gradient-to-r from-transparent via-accent to-transparent opacity-70"></div>
                {{-- heraldic corner brackets --}}
                <span class="pointer-events-none absolute left-3 top-3 h-4 w-4 border-l-2 border-t-2 border-accent opacity-50"></span>
                <span class="pointer-events-none absolute right-3 top-3 h-4 w-4 border-r-2 border-t-2 border-accent opacity-50"></span>
                <span class="pointer-events-none absolute left-3 bottom-3 h-4 w-4 border-l-2 border-b-2 border-accent opacity-50"></span>
                <span class="pointer-events-none absolute right-3 bottom-3 h-4 w-4 border-r-2 border-b-2 border-accent opacity-50"></span>

                <div class="relative flex items-center gap-4 sm:gap-5">
                    {{-- crest --}}
                    <svg class="h-16 w-16 shrink-0 sm:h-[72px] sm:w-[72px] drop-shadow" viewBox="0 0 64 72" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <defs>
                            <linearGradient id="crestFill" x1="32" y1="4" x2="32" y2="68" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#8A5CFF"/>
                                <stop offset="1" stop-color="#5A2FD6"/>
                            </linearGradient>
                        </defs>
                        <path d="M32 4 60 13v20c0 17-12 28-28 35C16 61 4 50 4 33V13L32 4Z" fill="url(#crestFill)" stroke="#F0A81F" stroke-width="3" stroke-linejoin="round"/>
                        <path d="M14 20 32 15l18 5" stroke="#F0A81F" stroke-width="1.5" stroke-linecap="round" opacity="0.7"/>
                        <text x="32" y="43" text-anchor="middle" font-family="'Baloo 2', system-ui, sans-serif" font-size="26" font-weight="800" fill="#FFFFFF">{{ $initials }}</text>
                    </svg>

                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-accent">Chief settings</p>
                        <h1 class="font-display font-extrabold text-2xl sm:text-3xl text-content tracking-tight truncate">{{ $user->name }}</h1>
                        <div class="mt-1.5 flex items-center gap-2 text-sm text-content-muted">
                            <span class="truncate">{{ $user->email }}</span>
                            @if ($user->hasVerifiedEmail())
                                <span class="inline-flex items-center gap-1 shrink-0 text-verified font-semibold">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 1.6 3 4.5v4.9c0 4.3 2.9 7.6 7 8.9 4.1-1.3 7-4.6 7-8.9V4.5L10 1.6Zm3.5 6.4-4.2 4.2a1 1 0 0 1-1.4 0L5.8 10a1 1 0 1 1 1.4-1.4l1.4 1.4 3.5-3.5A1 1 0 0 1 13.5 8Z" clip-rule="evenodd"/></svg>
                                    Verified
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 shrink-0 text-warning font-semibold">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.26 3.1c.77-1.33 2.71-1.33 3.48 0l6.28 10.87c.77 1.33-.2 3-1.74 3H3.72c-1.54 0-2.5-1.67-1.74-3L8.26 3.1ZM11 13a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm-1-2a1 1 0 0 1-1-1V7a1 1 0 1 1 2 0v3a1 1 0 0 1-1 1Z" clip-rule="evenodd"/></svg>
                                    Unverified
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sections --}}
            <div class="divide-y divide-line">

                <div class="grid gap-x-8 gap-y-4 p-6 sm:p-8 md:grid-cols-3">
                    <div>
                        <h2 class="flex items-center gap-2 font-display font-bold text-base text-content">
                            <span class="inline-block h-1.5 w-1.5 rotate-45 bg-accent"></span>
                            Chief profile
                        </h2>
                        <p class="mt-1 text-sm text-content-muted">Your name and the email you sign in with.</p>
                    </div>
                    <div class="md:col-span-2">
                        <livewire:profile.update-profile-information-form />
                    </div>
                </div>

                <div class="grid gap-x-8 gap-y-4 p-6 sm:p-8 md:grid-cols-3">
                    <div>
                        <h2 class="flex items-center gap-2 font-display font-bold text-base text-content">
                            <span class="inline-block h-1.5 w-1.5 rotate-45 bg-accent"></span>
                            Village defenses
                        </h2>
                        <p class="mt-1 text-sm text-content-muted">Change your password to keep your village secure.</p>
                    </div>
                    <div class="md:col-span-2">
                        <livewire:profile.update-password-form />
                    </div>
                </div>

                <div class="grid gap-x-8 gap-y-4 p-6 sm:p-8 md:grid-cols-3">
                    <div>
                        <h2 class="flex items-center gap-2 font-display font-bold text-base text-alert">
                            <span class="inline-block h-1.5 w-1.5 rotate-45 bg-alert"></span>
                            Abandon village
                        </h2>
                        <p class="mt-1 text-sm text-content-muted">Disables sign-in. Your record is kept for safety and audit.</p>
                    </div>
                    <div class="md:col-span-2">
                        <livewire:profile.delete-user-form />
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
