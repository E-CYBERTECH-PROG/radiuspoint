<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $tenant->company_name }} — WiFi</title>
    @include('captive-portal.partials._styles')
    <style>
        .hero { background: linear-gradient(120deg, var(--brand), #7c3aed); color: #fff; text-align: center; padding: 30px 24px; position: relative; overflow: hidden; }
        .hero::before { content: ""; position: absolute; top: -40px; right: -40px; width: 140px; height: 140px; border-radius: 50%; background: rgba(255,255,255,.12); }
        .hero::after { content: ""; position: absolute; bottom: -50px; left: -30px; width: 120px; height: 120px; border-radius: 50%; background: rgba(255,255,255,.08); }
        .hero .ribbon { position: relative; display: inline-block; background: #fff; color: var(--brand); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; padding: 5px 14px; border-radius: 999px; margin-bottom: 14px; }
        .hero img { position: relative; height: 46px; margin: 0 auto 8px; display: block; object-fit: contain; }
        .hero h1 { position: relative; font-size: 21px; margin: 6px 0 2px; font-weight: 800; }
        .hero p { position: relative; font-size: 13px; margin: 0; opacity: .9; }
    </style>
</head>
<body>

    <div class="card">
        <div class="hero">
            <div class="ribbon">⚡ Instant Connect</div>
            @if($portal?->logo_url)
                <img src="{{ $portal->logo_url }}" alt="{{ $tenant->company_name }}">
            @endif
            <h1>{{ $tenant->company_name }}</h1>
            <p>Grab a plan and get online now</p>
        </div>

        @include('captive-portal.partials._notice')
        @include('captive-portal.partials._plans')
        @include('captive-portal.partials._testimonials')
        @include('captive-portal.partials._footer')
    </div>

    @include('captive-portal.partials._modals')
    @include('captive-portal.partials._script')
</body>
</html>
