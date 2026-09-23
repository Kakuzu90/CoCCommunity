@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-primary text-sm font-display font-semibold text-content focus:outline-none transition'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-display font-semibold text-content-muted hover:text-content hover:border-line-strong focus:outline-none transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
