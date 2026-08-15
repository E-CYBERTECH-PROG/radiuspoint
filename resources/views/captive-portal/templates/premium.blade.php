<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $tenant->company_name }} — WiFi</title>
    {{-- Self-hosted only — see default.blade.php's comment for why (walled garden). --}}
    @include('captive-portal.partials._styles')
    <style>
        body { background: radial-gradient(circle at 50% 0%, #1e293b, #0f172a 60%); }
        .card { background: rgba(255,255,255,.97); box-shadow: 0 20px 60px rgba(0,0,0,.35); }
        .hero { background: linear-gradient(160deg, var(--brand), #0f172a 120%); color: #fff; text-align: center; padding: 36px 24px 30px; position: relative; overflow: hidden; }
        .hero::before { content: ""; position: absolute; top: -60%; left: -20%; width: 140%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,.12), transparent 60%); }
        .hero .crest { width: 52px; height: 52px; margin: 0 auto 12px; border-radius: 50%; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.25); display: flex; align-items: center; justify-content: center; position: relative; }
        .hero img { height: 32px; object-fit: contain; }
        .hero h1 { font-size: 21px; margin: 4px 0 4px; font-weight: 800; letter-spacing: -.01em; position: relative; }
        .hero p { font-size: 12px; margin: 0; opacity: .7; text-transform: uppercase; letter-spacing: .12em; font-weight: 600; position: relative; }
        .plan { border: 1px solid #eef0f3; background: linear-gradient(180deg, #fff, #fafafa); }
        .btn-brand { background: linear-gradient(135deg, var(--brand), #0f172a); }

        .rp-splash { background: radial-gradient(circle at 50% 50%, #1e293b, #0f172a 70%); }
        .rp-splash-glow { width: 56px; height: 56px; border-radius: 50%; border: 1px solid rgba(255,255,255,.25); animation: rp-breathe 1.6s ease-in-out infinite; }
        @keyframes rp-breathe { 0%, 100% { transform: scale(.85); opacity: .6; } 50% { transform: scale(1.05); opacity: 1; box-shadow: 0 0 30px 6px rgba(255,255,255,.15); } }
    </style>
</head>
<body>
    <script>window.__rpSplashStart = Date.now();</script>
    <div class="rp-splash" id="rp-splash">
        <div class="rp-splash-glow"></div>
    </div>

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
            <p>Premium WiFi Access</p>
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
