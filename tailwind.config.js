import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Montserrat', 'Figtree', ...defaultTheme.fontFamily.sans],
                fira: ['"Fira Code"', 'monospace'],
            },
            colors: {
                // Both scales anchored on the one-isp/Vuexy primary (#7367f0) — indigo drives
                // the sidebar/header (fixed, non-themeable), blue drives page-content's default
                // accent (see App\Support\ThemePalette, which overrides these per-user for
                // non-default theme choices but leaves them untouched otherwise).
                indigo: {
                    50: '#f7f6fe', 100: '#edebfd', 200: '#dbd7fb', 300: '#c4bff9', 400: '#9d95f5',
                    500: '#8176f2', 600: '#7367f0', 700: '#6359ce', 800: '#534aad', 900: '#433c8b', 950: '#2e2960',
                },
                blue: {
                    50: '#f7f6fe', 100: '#edebfd', 200: '#dbd7fb', 300: '#c4bff9', 400: '#9d95f5',
                    500: '#8176f2', 600: '#7367f0', 700: '#6359ce', 800: '#534aad', 900: '#433c8b', 950: '#2e2960',
                },
            },
        },
    },

    plugins: [forms],
};
