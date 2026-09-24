@props(['id', 'label', 'hint' => null, 'error' => null, 'value' => '', 'counter' => false, 'maxlength' => null])
@php($described = trim(($hint ? "$id-hint " : '').($error ? "$id-error " : '').($counter ? "$id-count " : '').$attributes->get('aria-describedby', '')))
<div x-data="{ count: 0 }" x-init="count = [...$refs.input.value].length">
<x-ui.field :id="$id" :label="$label" :hint="$hint" :error="$error">
    <div class="ui-control-wrap">
<textarea id="{{ $id }}" x-ref="input" @input="count = [...$event.target.value].length" {{ $attributes->except('aria-describedby')->class('ui-control') }} @if($maxlength) maxlength="{{ $maxlength }}" 
@endif
 @if($error) aria-invalid="true" 
@endif
 @if($described) aria-describedby="{{ $described }}" 
@endif
>{{ $value ?: $slot }}</textarea>
</div>
    @if($counter)<p id="{{ $id }}-count" class="ui-help">
<span x-text="count">{{ mb_strlen($value) }}</span>@if($maxlength) / {{ $maxlength }}
@endif
 characters</p>
@endif

</x-ui.field>
</div>
