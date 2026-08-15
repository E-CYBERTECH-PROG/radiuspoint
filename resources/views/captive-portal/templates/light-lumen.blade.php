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
            --card-shadow: 0 25px 70px -24px color-mix(in srgb, var(--brand) 45%, transparent), 0 8px 24px rgba(15,23,42,.06);
        }
        body { background: radial-gradient(circle at 50% 0%, #eef2ff 0%, #f8fafc 55%, #f1f5f9 100%); }

        .hero { background: linear-gradient(135deg, var(--brand), color-mix(in srgb, var(--brand) 65%, #818cf8)); color: #fff; text-align: center; padding: 32px 24px 28px; position: relative; overflow: hidden; }
        .hero::before { content: ""; position: absolute; top: -50%; left: 50%; transform: translateX(-50%); width: 220px; height: 220px; border-radius: 50%; background: radial-gradient(circle, rgba(255,255,255,.35), transparent 70%); }
        .hero img { position: relative; height: 46px; margin: 0 auto 10px; display: block; object-fit: contain; }
        .hero h1 { position: relative; font-size: 20px; margin: 6px 0 2px; font-weight: 800; }
        .hero p { position: relative; font-size: 13px; margin: 0; opacity: .9; }

        .plan:hover { border-color: var(--brand); box-shadow: 0 14px 34px -12px color-mix(in srgb, var(--brand) 50%, transparent); }
        .btn-brand { box-shadow: 0 10px 24px -8px color-mix(in srgb, var(--brand) 65%, transparent); }
        .btn-brand:not(:disabled):hover { box-shadow: 0 12px 30px -8px color-mix(in srgb, var(--brand) 75%, transparent); opacity: 1; }

        .rp-splash { background: radial-gradient(circle at 50% 50%, #eef2ff, #f8fafc 70%); }
        .rp-splash-orb { width: 60px; height: 60px; border-radius: 50%; background: radial-gradient(circle, var(--brand), transparent 72%); animation: rp-breathe-glow 1.8s ease-in-out infinite; }
        @keyframes rp-breathe-glow { 0%, 100% { transform: scale(.75); opacity: .45; } 50% { transform: scale(1.15); opacity: 1; } }
    </style>
</head>
<body>
    <script>window.__rpSplashStart = Date.now();</script>
    <div class="rp-splash" id="rp-splash"><div class="rp-splash-orb"></div></div>

    <div class="card">
        <div class="hero">
            @if($portal?->logo_url)
                <img src="{{ $portal->logo_url }}" alt="{{ $tenant->company_name }}">
            @else
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="margin:0 auto 8px" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 12.5C8.5 9 15.5 9 19 12.5" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                    <path d="M8 16C10 14 14 14 16 16" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="12" cy="19" r="1.5" fill="#fff"/>
                </svg>
            @endif
            <h1>{{ $tenant->company_name }}</h1>
            <p>Get connected in seconds</p>
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
