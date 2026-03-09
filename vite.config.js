import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/snackbar.css',
                'resources/views/dashboard/dashboard.css',
                'resources/views/dashboard/dashboard.js',
                'resources/views/workflow/workflow.css',
                'resources/views/workflow/workflow.js',
                'resources/views/login/login.css',
                'resources/views/Analitica/analitica.css',
                'resources/views/Analitica/analitica.js',
                'resources/views/seguimiento/seguimiento.css',
                'resources/views/seguimiento/seguimiento.js',
            ],
            refresh: true,
        }),
        viteStaticCopy({
            targets: [
                { src: 'resources/css/all.css', dest: 'assets/css' },
                { src: 'resources/assets/fonts/*', dest: 'assets/fonts' },
                { src: 'resources/assets/icons/*', dest: 'assets/icons' },
            ]
        }),
    ],
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: true,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        hmr: {
            host: 'localhost',
            port: 5173,
        },
    },
});
