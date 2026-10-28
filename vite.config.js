import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/bootstrap-ltr.css', 'resources/css/bootstrap-rtl.css', 'resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
