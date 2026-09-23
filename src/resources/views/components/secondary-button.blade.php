<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-display font-semibold text-sm text-content bg-surface border border-line-strong hover:bg-surface-2 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-surface transition disabled:opacity-60']) }}>
    {{ $slot }}
</button>
