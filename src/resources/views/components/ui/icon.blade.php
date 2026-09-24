@props(['name', 'size' => 20])
@php
    if (! in_array($name, ['check', 'close', 'plus', 'chevron', 'arrow', 'info', 'star', 'shield', 'layers', 'search', 'spinner', 'heart', 'home', 'bell', 'user', 'menu'], true)) throw new InvalidArgumentException('Unknown platform icon.');
@endphp
<svg {{ $attributes->class('ui-icon') }} data-size="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
<use href="#ui-icon-{{ $name }}" />
</svg>
