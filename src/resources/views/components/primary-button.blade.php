<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-display font-semibold text-sm text-on-primary bg-primary hover:bg-primary-hi focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-surface active:scale-[.98] transition disabled:opacity-60 shadow-[0_4px_14px_rgba(108,59,245,.35)]']) }}>
    {{ $slot }}
</button>
