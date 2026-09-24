@props(['id', 'label', 'hint' => null, 'error' => null, 'prefix' => null, 'suffix' => null])
@php($described = trim(($hint ? "$id-hint " : '').($error ? "$id-error " : '').$attributes->get('aria-describedby', '')))
<x-ui.field :id="$id" :label="$label" :hint="$hint" :error="$error">
    <div class="ui-control-wrap">
        @if($prefix)<span class="ui-affix" aria-hidden="true">{{ $prefix }}</span>
@endif

        <input id="{{ $id }}" {{ $attributes->except('aria-describedby')->class('ui-control')->merge(['type' => 'text']) }} @if($error) aria-invalid="true" 
@endif
 @if($described) aria-describedby="{{ $described }}" 
@endif
>
        @if($suffix)<span class="ui-affix" aria-hidden="true">{{ $suffix }}</span>
@endif

    </div>
</x-ui.field>
