@if(config('services.turnstile.enabled'))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.sitekey') }}" data-theme="dark"></div>
    @error('cf-turnstile-response')<p class="ui-error" role="alert">{{ $message }}</p>@enderror
@endif
