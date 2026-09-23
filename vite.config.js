import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            publicDirectory: 'public_html',
        }),
        tailwindcss(),
    ],
    // Not 'public_html'. Vite copies its publicDir into the build output, so that
    // setting copied the whole web root into public_html/build on every deploy:
    // index.php, .htaccess, a stray phpinfo file and a 240 MB copy of every upload
    // behind the storage link, all served again under /build/. The Laravel plugin's
    // `publicDirectory` above is what points the build at public_html.
    publicDir: false,
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
