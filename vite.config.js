import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/all.css', 'resources/css/sidebar.css'],
            refresh: true,
        }),
        viteStaticCopy({
            targets: [
                { src: 'resources/css/all.css', dest: 'assets/css' },
                { src: 'resources/assets/fonts/*', dest: 'assets/fonts' },
                { src: 'resources/assets/icons/*', dest: 'assets/icons' },
                { src: 'resources/css/govco.css', dest: 'assets/css' },
                { src: 'resources/css/sidebar.css', dest: 'assets/css' }
            ]
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
