@if (session('status'))
    <x-ui.alert tone="success" class="auth-status">{{ session('status') }}</x-ui.alert>
@endif
