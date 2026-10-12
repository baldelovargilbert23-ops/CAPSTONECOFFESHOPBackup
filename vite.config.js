import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
       laravel({
    input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/js/preline.js',
    ],
    refresh: true,
}),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '192.168.43.76',
        },
        cors: true,
    },
});
