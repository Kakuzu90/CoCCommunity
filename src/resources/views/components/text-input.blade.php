@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl bg-surface-2 border-line text-content placeholder-content-faint shadow-sm focus:border-primary focus:ring-2 focus:ring-primary transition disabled:opacity-60']) }}>
