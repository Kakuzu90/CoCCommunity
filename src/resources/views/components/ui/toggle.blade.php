@props(['id', 'label'])
<label for="{{ $id }}" class="ui-check ui-toggle">
<input type="checkbox" role="switch" id="{{ $id }}" {{ $attributes }}>
<span>{{ $label }}</span>
</label>
