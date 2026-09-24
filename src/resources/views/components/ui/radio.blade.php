@props(['id', 'label'])
<label for="{{ $id }}" class="ui-check">
<input type="radio" id="{{ $id }}" {{ $attributes }}>
<span>{{ $label }}</span>
</label>
