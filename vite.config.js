import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/personal-acc/css/style.css',
                'resources/personal-acc/js/app.js',
                'resources/js/personal-acc-blade.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
