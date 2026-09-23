<x-app-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-6">

        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-primary">Settings</p>
            <h1 class="mt-1 font-display font-extrabold text-2xl sm:text-3xl text-content tracking-tight">Profile</h1>
        </div>

        <div class="p-4 sm:p-8 bg-surface border border-line shadow-sm rounded-2xl">
            <div class="max-w-xl">
                <livewire:profile.update-profile-information-form />
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-surface border border-line shadow-sm rounded-2xl">
            <div class="max-w-xl">
                <livewire:profile.update-password-form />
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-surface border border-line shadow-sm rounded-2xl">
            <div class="max-w-xl">
                <livewire:profile.delete-user-form />
            </div>
        </div>

    </div>
</x-app-layout>
