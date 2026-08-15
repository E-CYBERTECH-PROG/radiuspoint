<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $tenant->company_name }} — WiFi</title>
    {{-- Self-hosted only — see partials/_styles.blade.php's comment for why (walled garden). --}}
    @include('captive-portal.partials._styles')
    <style>
        :root {
            --bg: #f8fafc;
            --card-shadow: 0 16px 40px -16px rgba(15,23,42,.18);
        }

        .hero { background: var(--brand); color: #fff; text-align: center; padding: 30px 24px; }
        .hero img { height: 46px; margin: 0 auto 8px; display: block; object-fit: contain; }
        .hero h1 { font-size: 19px; margin: 6px 0 2px; }
        .hero p { font-size: 13px; margin: 0; opacity: .85; }

        .plan .box-icon { display: block; width: 26px; height: 26px; color: var(--brand); margin-bottom: 8px; }
        .plan.is-popular { border-color: var(--brand); border-width: 2px; animation: rp-popular-pulse 2.5s ease-in-out infinite; }
        @keyframes rp-popular-pulse {
            0%, 100% { box-shadow: 0 16px 34px -14px color-mix(in srgb, var(--brand) 45%, transparent); }
            50% { box-shadow: 0 16px 34px -14px color-mix(in srgb, var(--brand) 75%, transparent); }
        }
        .plan .ribbon { display: block; position: absolute; top: -1px; right: 14px; background: var(--brand); color: #fff; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; padding: 4px 9px; border-radius: 0 0 6px 6px; }

        .rp-splash { background: #f8fafc; }
        .rp-splash-box { width: 42px; height: 42px; color: var(--brand); animation: rp-bounce-box 1s ease-in-out infinite; }
        @keyframes rp-bounce-box { 0%, 100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-10px) rotate(-6deg); } }
    </style>
</head>
<body class="@if($portal?->show_navbar) has-navbar @endif">
    <script>window.__rpSplashStart = Date.now();</script>
    <div class="rp-splash" id="rp-splash">
        <svg class="rp-splash-box" viewBox="0 0 24 24" fill="none"><path d="M3 8l9-5 9 5-9 5-9-5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M3 8v8l9 5 9-5V8" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 13v8" stroke="currentColor" stroke-width="1.5"/></svg>
    </div>

    @if($portal?->show_navbar)
        <div class="rp-navbar">
            @if($portal?->logo_url)
                <img src="{{ $portal->logo_url }}" alt="{{ $tenant->company_name }}">
            @endif
            <span class="rp-navbar-name">{{ $tenant->company_name }}</span>
        </div>
    @endif

    <div class="card">
        <div class="hero">
            @if($portal?->logo_url)
                <img src="{{ $portal->logo_url }}" alt="{{ $tenant->company_name }}">
            @else
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="margin:0 auto 8px"><path d="M3 8l9-5 9 5-9 5-9-5Z" stroke="#fff" stroke-width="1.5" stroke-linejoin="round"/><path d="M3 8v8l9 5 9-5V8" stroke="#fff" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 13v8" stroke="#fff" stroke-width="1.5"/></svg>
            @endif
            <h1>{{ $tenant->company_name }}</h1>
            <p>Pick your package</p>
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
