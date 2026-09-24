@props(['id', 'label', 'indeterminate' => false])
<label for="{{ $id }}" class="ui-check" @if($indeterminate) x-data x-init="$refs.input.indeterminate = true" 
@endif
>
    <input type="checkbox" id="{{ $id }}" @if($indeterminate) x-ref="input" 
@endif
 {{ $attributes }}>
<span>{{ $label }}</span>
</label>
