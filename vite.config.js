import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
        react()
    ],
    resolve : {
        alias: {
            '@': new URL('./resources/js', import.meta.url).pathname,
        },
    },
    server: {
        // Hot file dan HMR memakai IP host agar bisa diakses dari perangkat LAN.
        origin: process.env.VITE_SERVER_ORIGIN || undefined,
        // Secara default plugin Laravel menyamakan CORS dengan `origin` di atas.
        // Kita izinkan juga origin aplikasi (localhost dan APP_URL) agar
        // script Vite tidak diblokir saat aplikasi dibuka lewat localhost.
        cors: {
            origin: [
                /^https?:\/\/(?:(?:[^:]+\.)?localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$/,
                process.env.APP_URL || 'http://localhost',
                // Tautan lama dengan :8000 tetap diizinkan.
                process.env.APP_URL ? `${process.env.APP_URL}:8000` : 'http://localhost:8000',
            ],
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
