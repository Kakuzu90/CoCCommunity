@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-semibold uppercase tracking-wider text-content-muted']) }}>
    {{ $value ?? $slot }}
</label>
