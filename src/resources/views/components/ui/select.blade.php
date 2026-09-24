@props(['id', 'label', 'options' => [], 'value' => '', 'hint' => null, 'error' => null, 'searchable' => false])
@php($described = trim(($hint ? "$id-hint " : '').($error ? "$id-error " : '').$attributes->get('aria-describedby', '')))
<div x-data="{ query: '' }">
    @if($searchable)<x-ui.input :id="$id.'-filter'" :label="'Filter '.$label.' options'" type="search" x-model="query" :disabled="$attributes->has('disabled')" />
@endif

    <x-ui.field :id="$id" :label="$label" :hint="$hint" :error="$error">
        <div class="ui-control-wrap">
<select id="{{ $id }}" {{ $attributes->except('aria-describedby')->class('ui-control') }} @if($error) aria-invalid="true" 
@endif
 @if($described) aria-describedby="{{ $described }}" 
@endif
>
            <option value="">Choose an option</option>
            @foreach($options as $key => $text)<option value="{{ $key }}" @selected((string) $value === (string) $key) @if($searchable) :hidden="! {{ Illuminate\Support\Js::from(mb_strtolower($text)) }}.includes(query.toLowerCase())" 
@endif
>{{ $text }}</option>

@endforeach

        </select>
</div>
    </x-ui.field>
    @if($searchable)
        <p class="ui-help" role="status" x-cloak x-show="query && ! {{ Illuminate\Support\Js::from(array_values($options)) }}.some(option => option.toLowerCase().includes(query.toLowerCase()))">No matching options.</p>
    @endif
</div>
