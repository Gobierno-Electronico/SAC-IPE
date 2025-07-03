import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/css/layouts/app.css',
                'resources/css/layouts/loading.css',
                'resources/css/layouts/loadingDots.css',
            ],
            refresh: true,
        }),
    ],

    // server: {
    //     host: '10.0.2.62',
    //     port: 8003,
    //     strictPort: true
    // }
});
