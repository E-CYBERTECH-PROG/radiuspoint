<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'RadiusPoint') }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.scss', 'resources/js/app.js'])
    </head>
    <body class="d-flex flex-column">
        <div class="page page-center">
            <div class="container container-tight py-4">
                <div class="text-center mb-4">
                    <a href="/" class="d-inline-flex align-items-center gap-2 text-decoration-none">
                        <x-application-logo class="text-primary" style="width:2.25rem;height:2.25rem" />
                        <span class="fs-3 fw-bold text-body">RadiusPoint</span>
                    </a>
                </div>

                <div class="card card-md">
                    <div class="card-body">
                        {{ $slot }}
                    </div>
                </div>

                <div class="text-center text-muted mt-3 small">
                    &copy; {{ date('Y') }} RadiusPoint. All rights reserved.
                </div>
            </div>
        </div>
    </body>
</html>
