<x-layouts.app title="Edit profile" description="Edit your public Clash Commons profile.">
    @php($username = auth()->user()->username)
    @php($avatarName = $profile->displayName ?: $username)
    @php($p = config('accounts.profile'))
    @php($messages = ['profile-updated' => 'Your profile has been saved.', 'avatar-updated' => 'Your new avatar is being processed and will appear shortly.', 'avatar-removed' => 'Your avatar has been removed.'])

    <div class="settings-page">
        <header class="settings-head">
            <p class="ui-eyebrow">Settings</p>
            <h1>Edit profile</h1>
            <p class="ui-help">This is what other players see. Your username stays <strong>{{ '@'.$username }}</strong>.</p>
        </header>

        @if(session('status') && isset($messages[session('status')]))
            <x-ui.alert tone="success">{{ $messages[session('status')] }}</x-ui.alert>
        @endif
        @error('media')<x-ui.alert tone="danger">{{ $message }}</x-ui.alert>@enderror

        <section class="settings-card" x-data="avatarUploader">
            <h2 class="settings-card-title">Avatar</h2>
            <div class="avatar-editor">
                <template x-if="preview">
                    <span class="ui-avatar" data-size="96" role="img" aria-label="New avatar preview"><img :src="preview" alt="" width="96" height="96"></span>
                </template>
                <template x-if="!preview">
                    <span>
                        <x-ui.avatar :name="$avatarName" :src="$profile->avatar?->url('card')" size="96" />
                    </span>
                </template>

                <div class="avatar-editor-controls">
                    <label class="ui-button" data-variant="secondary" data-size="sm">
                        <span>Upload new</span>
                        <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" x-on:change="pick($event)" x-bind:disabled="busy">
                    </label>

                    @if($profile->avatar)
                        <form method="POST" action="{{ route('settings.profile.avatar.destroy') }}">
                            @csrf @method('DELETE')
                            <x-ui.button variant="ghost" size="sm" type="submit">Remove</x-ui.button>
                        </form>
                    @endif

                    <p class="ui-help" aria-live="polite">
                        <span x-show="statusText" x-text="statusText"></span>
                        <span class="text-danger" x-show="error" x-text="error"></span>
                        <span x-show="!statusText && !error">JPG, PNG or WebP, up to 2&nbsp;MB. Square works best.</span>
                    </p>
                </div>
            </div>

            {{-- Submitted by the uploader once the pipeline reports the media ready. --}}
            <form method="POST" action="{{ route('settings.profile.avatar.store') }}" x-ref="attachForm" hidden>
                @csrf
                <input type="hidden" name="media_ulid" x-bind:value="ulid">
            </form>
        </section>

        <form method="POST" action="{{ route('settings.profile.update') }}" class="settings-card">
            @csrf @method('PUT')
            <h2 class="settings-card-title">Details</h2>

            <x-ui.input id="display_name" name="display_name" label="Display name"
                :value="old('display_name', $profile->displayName)" maxlength="{{ $p['display_name_max'] }}"
                hint="Shown instead of your username. Leave blank to use {{ '@'.$username }}."
                :error="$errors->first('display_name')" />

            <x-ui.textarea id="bio" name="bio" label="Bio" :counter="true" maxlength="{{ $p['bio_max'] }}"
                :value="old('bio', $profile->bio)" hint="A short introduction. Plain text."
                :error="$errors->first('bio')" />

            <div class="settings-grid">
                <x-ui.input id="country_code" name="country_code" label="Country"
                    :value="old('country_code', $profile->countryCode)" maxlength="2"
                    hint="Two-letter code, e.g. GB." :error="$errors->first('country_code')" />

                <x-ui.input id="timezone" name="timezone" label="Timezone"
                    :value="old('timezone', $profile->timezone)"
                    hint="e.g. Europe/London." :error="$errors->first('timezone')" />
            </div>

            <fieldset class="settings-fieldset">
                <legend class="ui-label">Languages</legend>
                <p class="ui-help">Up to {{ $p['languages_max'] }} two-letter codes, e.g. en.</p>
                <div class="settings-grid settings-grid-3">
                    @for($i = 0; $i < $p['languages_max']; $i++)
                        <x-ui.input :id="'language_'.$i" name="languages[]" label="Language {{ $i + 1 }}"
                            :value="old('languages.'.$i, $profile->languages[$i] ?? '')" maxlength="2" />
                    @endfor
                </div>
                @error('languages')<p class="ui-error">{{ $message }}</p>@enderror
                @error('languages.*')<p class="ui-error">{{ $message }}</p>@enderror
            </fieldset>

            <fieldset class="settings-fieldset">
                <legend class="ui-label">Links</legend>
                <div class="settings-grid">
                    @foreach($p['socials'] as $platform)
                        <x-ui.input :id="'social_'.$platform" name="socials[{{ $platform }}]" label="{{ ucfirst($platform) }}"
                            :value="old('socials.'.$platform, $profile->socials[$platform] ?? '')"
                            maxlength="{{ $p['social_max'] }}" :error="$errors->first('socials.'.$platform)" />
                    @endforeach
                </div>
            </fieldset>

            <div class="settings-actions">
                <x-ui.button type="submit">Save changes</x-ui.button>
            </div>
        </form>
    </div>
</x-layouts.app>
