@props(['variant' => 'primary', 'size' => 'md', 'block' => false, 'iconOnly' => false, 'loading' => false, 'disabled' => false])
@php
    if (! in_array($variant, ['primary', 'secondary', 'ghost', 'danger', 'success'], true) || ! in_array($size, ['sm', 'md', 'lg'], true)) throw new InvalidArgumentException('Unknown button variant or size.');
    if ($iconOnly && ! $attributes->get('aria-label')) throw new InvalidArgumentException('Icon buttons require aria-label.');
@endphp
<button {{ $attributes->class('ui-button')->merge(['type' => 'button']) }} data-variant="{{ $variant }}" data-size="{{ $size }}" @if($block) data-block 
@endif
 @if($iconOnly) data-icon 
@endif
 @disabled($disabled || $loading) @if($loading) aria-busy="true" 
@endif
>
    <span class="ui-button-label">{{ $slot }}</span>
    @if($loading)<span class="ui-button-spinner">
<x-ui.icon name="spinner" class="ui-spin" />
</span>
<span class="sr-only">Loading</span>
@endif

</button>
