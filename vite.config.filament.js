import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { createRequire } from 'node:module';
import path from 'node:path';

const req = createRequire(import.meta.url);
const tw3Path = path.dirname(req.resolve('tailwindcss-v3/package.json'));

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/filament/admin/theme.css'],
            refresh: true,
            buildDirectory: 'build/filament',
        }),
    ],
    resolve: {
        alias: {
            tailwindcss: tw3Path,
        },
    },
    css: {
        postcss: {
            plugins: [
                req('tailwindcss-v3/nesting')('postcss-nesting'),
                req('tailwindcss-v3')({
                    config: 'resources/css/filament/admin/tailwind.config.js',
                }),
                req('autoprefixer'),
            ],
        },
    },
});
