<x-layouts.app title="Privacy settings" description="Choose who can see your Clash Commons profile.">
    <div class="settings-page">
        <header class="settings-head">
            <p class="ui-eyebrow">Settings</p>
            <h1>Privacy</h1>
            <p class="ui-help">Control who sees your profile and what you share.</p>
            @include('settings.partials.nav')
            <p><a href="{{ route('profile.show', auth()->user()->username) }}">View profile</a></p>
        </header>

        @if(session('status') === 'privacy-updated')
            <x-ui.alert tone="success">Your privacy settings have been saved.</x-ui.alert>
        @endif

        <form method="POST" action="{{ route('settings.privacy.update') }}" class="settings-card">
            @csrf @method('PUT')
            <h2 class="settings-card-title">Profile visibility</h2>
            <fieldset class="settings-fieldset">
                <legend class="ui-label">Who can see your profile?</legend>
                @foreach(\App\Domain\Users\Enums\ProfileVisibility::cases() as $option)
                    <label class="ui-check"><input type="radio" name="profile_visibility" value="{{ $option->value }}" @checked(old('profile_visibility', $privacy->visibility->value) === $option->value)> {{ $option->label() }}</label>
                @endforeach
                @error('profile_visibility')<p class="ui-error">{{ $message }}</p>@enderror
            </fieldset>

            <h2 class="settings-card-title">Shared details</h2>
            @foreach([
                'show_coc_accounts' => ['Show connected accounts', $privacy->showCocAccounts],
                'show_clan' => ['Show clan', $privacy->showClan],
                'show_activity' => ['Show activity', $privacy->showActivity],
                'allow_recruitment_contact' => ['Allow recruitment contact', $privacy->allowRecruitmentContact],
                'allow_marketplace_contact' => ['Allow marketplace contact', $privacy->allowMarketplaceContact],
                'searchable' => ['Include public profile in search', $privacy->searchable],
            ] as $name => [$label, $enabled])
                <input type="hidden" name="{{ $name }}" value="0">
                <x-ui.toggle :id="$name" :label="$label" name="{{ $name }}" value="1" :checked="(bool) old($name, $enabled)" />
                @error($name)<p class="ui-error">{{ $message }}</p>@enderror
            @endforeach
            <p class="ui-help">Account, clan, activity and contact controls will apply when those features arrive.</p>
            <div class="settings-actions"><x-ui.button type="submit">Save privacy settings</x-ui.button></div>
        </form>
    </div>
</x-layouts.app>
