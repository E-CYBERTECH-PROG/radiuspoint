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
            --card-bg: rgba(255,255,255,.06);
            --card-border: rgba(255,255,255,.14);
            --card-shadow: 0 25px 70px rgba(0,0,0,.55), inset 0 1px 0 rgba(255,255,255,.07);
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --surface-2: rgba(255,255,255,.05);
            --border: rgba(255,255,255,.12);
            --divider: rgba(255,255,255,.1);
            --input-bg: rgba(255,255,255,.06);
            --input-border: rgba(255,255,255,.18);
            --modal-bg: rgba(15,23,42,.9);
            --free-bg: rgba(34,197,94,.12);
            --free-text: #4ade80;
            --free-border: rgba(74,222,128,.3);
            --spinner-track: rgba(255,255,255,.15);
        }
        body { background: radial-gradient(circle at 50% 0%, #1e293b, #020617 70%); background-size: 140% 140%; animation: rp-drift 18s ease-in-out infinite; }
        @keyframes rp-drift { 0%, 100% { background-position: 50% 0%; } 50% { background-position: 45% 8%; } }
        .card, .modal, .testimonial, .action-card, .rp-navbar { backdrop-filter: blur(20px) saturate(150%); -webkit-backdrop-filter: blur(20px) saturate(150%); }

        .hero { background: linear-gradient(160deg, color-mix(in srgb, var(--brand) 50%, #0f172a), #0f172a 130%); color: #fff; text-align: center; padding: 34px 24px 28px; position: relative; overflow: hidden; }
        .hero::before { content: ""; position: absolute; top: -60%; left: -20%; width: 140%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,.1), transparent 60%); }
        .hero .crest { position: relative; width: 52px; height: 52px; margin: 0 auto 12px; border-radius: 50%; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; }
        .hero img { height: 30px; object-fit: contain; }
        .hero h1 { position: relative; font-size: 20px; margin: 4px 0 4px; font-weight: 800; }
        .hero p { position: relative; font-size: 12px; margin: 0; opacity: .65; text-transform: uppercase; letter-spacing: .1em; font-weight: 600; }

        .plan:hover { border-color: var(--brand); box-shadow: 0 0 0 1px var(--brand), 0 14px 34px -10px color-mix(in srgb, var(--brand) 55%, transparent); }
        .btn-brand:not(:disabled):hover { box-shadow: 0 0 20px -2px var(--brand); opacity: 1; }

        .rp-splash { background: radial-gradient(circle at 50% 50%, #1e293b, #020617 70%); }
        .rp-splash-glow { width: 56px; height: 56px; border-radius: 50%; border: 1px solid rgba(255,255,255,.25); animation: rp-breathe 1.6s ease-in-out infinite; }
        @keyframes rp-breathe { 0%, 100% { transform: scale(.85); opacity: .6; } 50% { transform: scale(1.05); opacity: 1; box-shadow: 0 0 30px 6px rgba(255,255,255,.15); } }
    </style>
</head>
<body class="@if($portal?->show_navbar) has-navbar @endif">
    <script>window.__rpSplashStart = Date.now();</script>
    <div class="rp-splash" id="rp-splash"><div class="rp-splash-glow"></div></div>

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
            <div class="crest">
                @if($portal?->logo_url)
                    <img src="{{ $portal->logo_url }}" alt="{{ $tenant->company_name }}">
                @else
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M12 2 4 6v6c0 5 3.5 8.7 8 10 4.5-1.3 8-5 8-10V6l-8-4Z" stroke="#fff" stroke-width="1.5" stroke-linejoin="round"/></svg>
                @endif
            </div>
            <h1>{{ $tenant->company_name }}</h1>
            <p>Secure WiFi Access</p>
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
