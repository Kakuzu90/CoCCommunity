<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-display font-semibold text-sm text-white bg-alert hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-alert focus:ring-offset-2 focus:ring-offset-surface active:scale-[.98] transition disabled:opacity-60']) }}>
    {{ $slot }}
</button>
