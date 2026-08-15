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
            --bg: #fff;
            --card-border: #111827;
            --card-radius: 6px;
            --card-shadow: none;
            --border: #111827;
            --divider: #e4e4e7;
            --plan-radius: 4px;
            --btn-radius: 4px;
            --text: #09090b;
            --text-muted: #52525b;
            --surface-2: #fafafa;
        }
        .card { border-width: 2px; }
        .plan { border-width: 2px; }
        .plan:hover { transform: none; box-shadow: none; border-color: #111827; }
        .action-card { border-width: 2px; }
        .action-card:not(:disabled):hover { transform: none; box-shadow: none; border-color: #111827; }
        .btn-brand:not(:disabled):hover { opacity: .85; }

        .hero { padding: 28px 24px; border-bottom: 2px solid #111827; text-align: left; }
        .hero img { height: 38px; display: block; margin-bottom: 10px; object-fit: contain; }
        .hero h1 { font-size: 21px; font-weight: 800; margin: 0; }
        .hero p { font-size: 11px; color: var(--text-muted); margin: 4px 0 0; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; }
        .plan .price { font-size: 24px; }

        .rp-splash { background: #fff; }
        .rp-splash-squares { display: flex; gap: 6px; }
        .rp-splash-squares span { width: 11px; height: 11px; background: #111827; opacity: .2; animation: rp-fill .9s ease-in-out infinite; }
        .rp-splash-squares span:nth-child(2) { animation-delay: .15s; }
        .rp-splash-squares span:nth-child(3) { animation-delay: .3s; }
        @keyframes rp-fill { 0%, 100% { opacity: .2; } 50% { opacity: 1; } }
    </style>
</head>
<body class="@if($portal?->show_navbar) has-navbar @endif">
    <script>window.__rpSplashStart = Date.now();</script>
    <div class="rp-splash" id="rp-splash">
        <div class="rp-splash-squares"><span></span><span></span><span></span></div>
    </div>

    @if($portal?->show_navbar)
        <div class="rp-navbar" style="border-bottom-width: 2px;">
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
            @endif
            <h1>{{ $tenant->company_name }}</h1>
            <p>WiFi Access</p>
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
