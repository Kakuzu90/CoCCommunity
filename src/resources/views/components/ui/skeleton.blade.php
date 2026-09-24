@props(['variant' => 'text-line', 'label' => 'Loading'])
<div {{ $attributes->class('ui-skeleton') }} data-variant="{{ $variant }}" role="status" aria-label="{{ $label }}" aria-busy="true">
<span class="sr-only">{{ $label }}</span>
</div>
