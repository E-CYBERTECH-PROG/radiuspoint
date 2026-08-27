<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-10 sm:pt-0 px-4 bg-gray-50" style="background-image: radial-gradient(circle at 15% 0%, rgba(79,70,229,.06), transparent 40%), radial-gradient(circle at 85% 100%, rgba(79,70,229,.05), transparent 40%);">
            <a href="/" class="flex items-center gap-2.5">
                <x-application-logo class="w-9 h-9 text-indigo-600" />
                <span class="text-lg font-bold tracking-tight text-gray-900">RadiusPoint</span>
            </a>

            <div class="w-full sm:max-w-md mt-8 px-6 py-8 sm:px-10 bg-white shadow-xl shadow-gray-900/5 ring-1 ring-gray-900/5 overflow-hidden sm:rounded-2xl">
                {{ $slot }}
            </div>

            <p class="mt-8 text-xs text-gray-400">&copy; {{ date('Y') }} RadiusPoint. All rights reserved.</p>
        </div>
    </body>
</html>
