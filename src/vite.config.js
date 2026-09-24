import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        // Bind on all interfaces so the container is reachable from the host,
        // but advertise localhost to the browser via HMR — otherwise the hot
        // file points at 0.0.0.0 and the browser can't reach the assets.
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
        // The app runs on :8080 and Vite on :5173, so every asset request is
        // cross-origin. Vite 7 tightened dev-server CORS (only its own origin is
        // allowed by default), which blocks those requests. Allow localhost on
        // any port so scripts and fonts load in local dev.
        cors: {
            origin: /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
