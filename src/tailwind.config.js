import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // Explicit-toggle dark mode; the CSS variables in app.css also flip on the
    // OS preference, so tokened colors theme without needing dark: variants.
    darkMode: ['selector', '[data-theme="dark"]'],

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Rubik', ...defaultTheme.fontFamily.sans],
                display: ['"Baloo 2"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: 'var(--primary)',
                    hi: 'var(--primary-hi)',
                    soft: 'var(--primary-soft)',
                },
                accent: {
                    DEFAULT: 'var(--accent)',
                    soft: 'var(--accent-soft)',
                },
                verified: {
                    DEFAULT: 'var(--verified)',
                    soft: 'var(--verified-soft)',
                },
                alert: {
                    DEFAULT: 'var(--alert)',
                    soft: 'var(--alert-soft)',
                },
                warning: 'var(--warning)',
                'on-primary': 'var(--on-primary)',
                ground: 'var(--bg)',
                surface: {
                    DEFAULT: 'var(--surface)',
                    2: 'var(--surface-2)',
                    3: 'var(--surface-3)',
                },
                line: {
                    DEFAULT: 'var(--border)',
                    strong: 'var(--border-strong)',
                },
                content: {
                    DEFAULT: 'var(--text)',
                    muted: 'var(--muted)',
                    faint: 'var(--faint)',
                },
            },
        },
    },

    plugins: [forms],
};
