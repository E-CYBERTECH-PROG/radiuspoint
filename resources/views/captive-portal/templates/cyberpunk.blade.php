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
            --card-bg: rgba(20,8,40,.45);
            --card-border: rgba(217,70,239,.4);
            --card-shadow: 0 0 40px -6px rgba(217,70,239,.35), 0 0 70px -20px rgba(34,211,238,.3), 0 25px 60px rgba(0,0,0,.6);
            --text: #f5f3ff;
            --text-muted: #a78bfa;
            --surface-2: rgba(255,255,255,.04);
            --border: rgba(217,70,239,.3);
            --divider: rgba(217,70,239,.2);
            --input-bg: rgba(255,255,255,.05);
            --input-border: rgba(217,70,239,.35);
            --modal-bg: rgba(15,4,33,.92);
            --free-bg: rgba(34,211,238,.1);
            --free-text: #22d3ee;
            --free-border: rgba(34,211,238,.35);
            --spinner-track: rgba(217,70,239,.2);
        }
        body {
            background: radial-gradient(circle at 50% 0%, #1a0533, #05010f 75%);
            background-image:
                linear-gradient(rgba(217,70,239,.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(34,211,238,.08) 1px, transparent 1px),
                radial-gradient(circle at 50% 0%, #1a0533, #05010f 75%);
            background-size: 28px 28px, 28px 28px, 100% 100%;
        }
        .card, .modal, .testimonial, .action-card, .rp-navbar { backdrop-filter: blur(18px) saturate(160%); -webkit-backdrop-filter: blur(18px) saturate(160%); }
        .card { position: relative; overflow: hidden; }
        .card::before { content: ""; position: absolute; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, #22d3ee, #d946ef, transparent); animation: rp-scanline 3s linear infinite; box-shadow: 0 0 8px 1px rgba(34,211,238,.6); }
        @keyframes rp-scanline { 0% { top: 0; } 100% { top: 100%; } }

        .hero { background: linear-gradient(160deg, rgba(217,70,239,.25), rgba(34,211,238,.15) 130%); color: #fff; text-align: center; padding: 32px 24px 26px; position: relative; overflow: hidden; border-bottom: 1px solid var(--border); }
        .hero .crest { position: relative; width: 50px; height: 50px; margin: 0 auto 12px; border-radius: 12px; background: rgba(0,0,0,.3); border: 1px solid rgba(217,70,239,.5); display: flex; align-items: center; justify-content: center; box-shadow: 0 0 20px -4px rgba(217,70,239,.7); }
        .hero img { height: 28px; object-fit: contain; }
        .hero h1 { position: relative; font-size: 19px; margin: 4px 0 4px; font-weight: 800; letter-spacing: .02em; text-shadow: 0 0 12px rgba(217,70,239,.6); }
        .hero p { position: relative; font-size: 11px; margin: 0; color: #22d3ee; text-transform: uppercase; letter-spacing: .18em; font-weight: 700; text-shadow: 0 0 8px rgba(34,211,238,.6); }

        .btn-brand { background: linear-gradient(90deg, #d946ef, #22d3ee); text-transform: uppercase; letter-spacing: .06em; }
        .btn-brand:not(:disabled):hover { box-shadow: 0 0 24px -2px rgba(217,70,239,.8); opacity: 1; }
        .plan:hover { border-color: #22d3ee; box-shadow: 0 0 0 1px #22d3ee, 0 0 24px -6px rgba(34,211,238,.7); }

        .rp-splash { background: radial-gradient(circle at 50% 0%, #1a0533, #05010f 75%); }
        .rp-splash-hex { width: 52px; height: 52px; position: relative; }
        .rp-splash-hex svg { width: 100%; height: 100%; animation: rp-glitch 1.4s steps(2, end) infinite; filter: drop-shadow(0 0 8px rgba(217,70,239,.8)); }
        @keyframes rp-glitch {
            0%, 100% { opacity: 1; transform: translate(0, 0); }
            45% { opacity: 1; transform: translate(0, 0); }
            50% { opacity: .6; transform: translate(-2px, 1px); }
            55% { opacity: 1; transform: translate(2px, -1px); }
            60% { opacity: 1; transform: translate(0, 0); }
        }
    </style>
</head>
<body class="@if($portal?->show_navbar) has-navbar @endif">
    <script>window.__rpSplashStart = Date.now();</script>
    <div class="rp-splash" id="rp-splash">
        <div class="rp-splash-hex">
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 2 21 7v10l-9 5-9-5V7l9-5Z" stroke="#22d3ee" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 2 21 7v10l-9 5-9-5V7l9-5Z" stroke="#d946ef" stroke-width="1" stroke-linejoin="round" opacity=".6"/></svg>
        </div>
    </div>

    @if($portal?->show_navbar)
        <div class="rp-navbar">
            @if($portal?->logo_url)
                <img src="{{ $portal->logo_url }}" alt="{{ $tenant->company_name }}">
            @endif
            <span class="rp-navbar-name" style="text-shadow: 0 0 8px rgba(217,70,239,.6);">{{ $tenant->company_name }}</span>
        </div>
    @endif

    <div class="card">
        <div class="hero">
            <div class="crest">
                @if($portal?->logo_url)
                    <img src="{{ $portal->logo_url }}" alt="{{ $tenant->company_name }}">
                @else
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2 21 7v10l-9 5-9-5V7l9-5Z" stroke="#22d3ee" stroke-width="1.5" stroke-linejoin="round"/></svg>
                @endif
            </div>
            <h1>{{ $tenant->company_name }}</h1>
            <p>Jack In</p>
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
