import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            // universe.*: the 3D home page (home-universe.blade.php) — its own
            // entry so three.js never ships with the rest of the site.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/universe.css', 'resources/js/universe/main.js'],
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
    build: {
        rolldownOptions: {
            output: {
                // three.js in a chunk of its own: its hash only moves when the
                // library does, so a deploy that touches the universe's own code
                // leaves returning visitors' copy (~150 KB gzipped) in their cache.
                codeSplitting: {
                    groups: [{ name: 'three', test: /[\\/]node_modules[\\/]three[\\/]/ }],
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
