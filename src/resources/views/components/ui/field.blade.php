@props(['id', 'label', 'hint' => null, 'error' => null])
<div class="ui-field">
    <label for="{{ $id }}" class="ui-label">{{ $label }}</label>
    {{ $slot }}
    @if($hint)<p id="{{ $id }}-hint" class="ui-help">{{ $hint }}</p>
@endif

    @if($error)<p id="{{ $id }}-error" class="ui-error" role="alert">{{ $error }}</p>
@endif

</div>
