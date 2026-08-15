<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $tenant->company_name }} — WiFi</title>
    @include('captive-portal.partials._styles')
    <style>
        .hero { background: linear-gradient(135deg, #111827, var(--brand)); color: #fff; text-align: center; padding: 32px 24px 28px; position: relative; }
        .hero .badge { display: inline-flex; align-items: center; gap: 5px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2); border-radius: 999px; padding: 4px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 12px; }
        .hero img { height: 44px; margin: 0 auto 10px; display: block; object-fit: contain; }
        .hero h1 { font-size: 20px; margin: 4px 0 2px; font-weight: 800; }
        .hero p { font-size: 13px; margin: 0; opacity: .75; }

        .rp-splash { background: #111827; flex-direction: column; }
        .rp-splash-brand { color: #fff; font-weight: 800; font-size: 16px; margin-bottom: 16px; letter-spacing: .01em; }
        .rp-splash-bar { width: 140px; height: 4px; background: rgba(255,255,255,.15); border-radius: 999px; overflow: hidden; }
        .rp-splash-bar-fill { width: 40%; height: 100%; background: var(--brand); border-radius: 999px; animation: rp-sweep 1.1s ease-in-out infinite; }
        @keyframes rp-sweep { 0% { transform: translateX(-100%); } 100% { transform: translateX(350%); } }
    </style>
</head>
<body>
    <script>window.__rpSplashStart = Date.now();</script>
    <div class="rp-splash" id="rp-splash">
        <div class="rp-splash-brand">{{ $tenant->company_name }}</div>
        <div class="rp-splash-bar"><div class="rp-splash-bar-fill"></div></div>
    </div>

    <div class="card">
        <div class="hero">
            <div class="badge">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none"><path d="M12 2 4 6v6c0 5 3.5 8.7 8 10 4.5-1.3 8-5 8-10V6l-8-4Z" fill="#fff"/></svg>
                Secure Business WiFi
            </div>
            @if($portal?->logo_url)
                <img src="{{ $portal->logo_url }}" alt="{{ $tenant->company_name }}">
            @endif
            <h1>{{ $tenant->company_name }}</h1>
            <p>Fast, reliable internet for your visit</p>
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
