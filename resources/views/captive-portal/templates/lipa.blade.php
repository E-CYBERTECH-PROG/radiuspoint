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
        {{-- M-Pesa's own green (#00A651) is fixed here, not tied to --brand — this theme is
             specifically "pay with M-Pesa" branded, so the payment badge/accent should read
             as M-Pesa regardless of a tenant's own brand color. Buttons/pricing still use
             --brand so a tenant's own identity comes through elsewhere. --}}
        :root {
            --mpesa-green: #00a651;
            --card-bg: rgba(20,28,22,.55);
            --card-border: rgba(0,166,81,.28);
            --card-shadow: 0 25px 70px rgba(0,0,0,.6), 0 0 50px -20px rgba(0,166,81,.3);
            --text: #f3f7f4;
            --text-muted: #9db3a5;
            --surface-2: rgba(255,255,255,.04);
            --border: rgba(0,166,81,.2);
            --divider: rgba(255,255,255,.08);
            --input-bg: rgba(255,255,255,.05);
            --input-border: rgba(0,166,81,.3);
            --modal-bg: rgba(10,15,11,.92);
            --free-bg: rgba(0,166,81,.1);
            --free-text: #4ade80;
            --free-border: rgba(0,166,81,.35);
            --spinner-track: rgba(255,255,255,.15);
        }
        {{-- Layered, independently-drifting gradients read as an immersive atmospheric backdrop
             rather than a single flat panel — the "full-bleed hero" feel, without a stock photo. --}}
        body {
            background-color: #05100a;
            background-image:
                radial-gradient(circle at 20% -10%, rgba(0,166,81,.25), transparent 55%),
                radial-gradient(circle at 85% 10%, rgba(0,166,81,.12), transparent 50%),
                radial-gradient(circle at 50% 100%, #0f2417, #030805 70%);
            background-size: 140% 140%, 160% 160%, 100% 100%;
            animation: rp-mesh-drift 24s ease-in-out infinite alternate;
        }
        @keyframes rp-mesh-drift {
            0% { background-position: 0% 0%, 100% 0%, 50% 100%; }
            100% { background-position: 10% 10%, 85% 15%, 50% 100%; }
        }
        .card, .modal, .testimonial, .action-card, .rp-navbar { backdrop-filter: blur(18px) saturate(140%); -webkit-backdrop-filter: blur(18px) saturate(140%); }

        {{-- Thin Kenyan-flag accent under the hero — a tasteful nod, not a literal flag. --}}
        .card { position: relative; }
        .card::after { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #000 0 25%, #bb0000 25% 50%, #006600 50% 75%, #fff 75% 100%); }

        {{-- Staggered fade/slide entrance — distinct from every other theme's splash-only
             reveal. Each element's own delay is set inline via style="--d:Ns" below. --}}
        .rp-enter { opacity: 0; animation: rp-fade-up .6s ease-out forwards; animation-delay: var(--d, 0s); }
        @keyframes rp-fade-up { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .hero { background: linear-gradient(160deg, rgba(0,166,81,.22), rgba(5,16,10,.4) 130%); color: #fff; text-align: center; padding: 34px 24px 26px; position: relative; overflow: hidden; }
        .hero .crest { position: relative; width: 52px; height: 52px; margin: 0 auto 12px; border-radius: 50%; background: rgba(0,166,81,.15); border: 1px solid rgba(0,166,81,.5); display: flex; align-items: center; justify-content: center; }
        .hero img { height: 30px; object-fit: contain; }
        .hero h1 { position: relative; font-size: 20px; margin: 4px 0 6px; font-weight: 800; }
        .hero .mpesa-badge { position: relative; display: inline-flex; align-items: center; gap: 6px; background: var(--mpesa-green); color: #fff; font-size: 11px; font-weight: 800; letter-spacing: .03em; padding: 5px 12px; border-radius: 999px; box-shadow: 0 4px 14px -4px rgba(0,166,81,.7); }
        .hero p.tag { position: relative; font-size: 12px; margin: 8px 0 0; color: #cfe8da; }
        .hero .support { position: relative; font-size: 12px; margin: 10px 0 0; color: #9db3a5; }
        .hero .support b { color: #fff; font-weight: 800; }
        .hero .device { position: relative; font-size: 10px; margin: 6px 0 0; color: #6b8577; font-family: ui-monospace, monospace; }

        {{-- Horizontal offer-row layout instead of the shared vertical card, built purely from
             the existing plan markup (no _plans.blade.php changes) via CSS grid areas: left
             column stacks name/meta/tier, right column holds price (spanning both rows) then Buy. --}}
        .plans-grid { display: flex; flex-direction: column; grid-template-columns: none; }
        .plan {
            display: grid;
            grid-template-columns: 1fr auto;
            grid-template-areas: "name price" "meta price" "tier buy";
            column-gap: 16px;
            align-items: center;
            padding: 16px 18px;
        }
        .plan .name { grid-area: name; }
        .plan .meta { grid-area: meta; }
        .plan .tier { grid-area: tier; margin-top: 4px; }
        .plan .price { grid-area: price; align-self: center; font-size: 22px; margin-top: 0; }
        .plan .btn-buy { grid-area: buy; width: auto; margin-top: 8px; justify-self: end; padding: 8px 18px; }
        .plan:hover { border-color: var(--mpesa-green); box-shadow: 0 0 0 1px var(--mpesa-green), 0 14px 34px -12px rgba(0,166,81,.5); }
        .plan .price { color: var(--mpesa-green); }
        .btn-brand:not(:disabled):hover { box-shadow: 0 0 20px -4px var(--brand); opacity: 1; }

        {{-- Each row joins the same staggered entrance as the hero, continuing past it in time
             rather than needing its own class on markup we don't own (_plans.blade.php). --}}
        .plan { opacity: 0; animation: rp-fade-up .6s ease-out forwards; }
        .plan:nth-child(1) { animation-delay: .48s; }
        .plan:nth-child(2) { animation-delay: .56s; }
        .plan:nth-child(3) { animation-delay: .64s; }
        .plan:nth-child(4) { animation-delay: .72s; }
        .plan:nth-child(n+5) { animation-delay: .8s; }

        .rp-splash { background: radial-gradient(circle at 50% -10%, #0f2417, #05100a 65%); }
        .rp-splash-phone { position: relative; width: 46px; height: 46px; display: flex; align-items: center; justify-content: center; }
        .rp-splash-phone svg { position: relative; z-index: 1; filter: drop-shadow(0 0 6px rgba(0,166,81,.7)); }
        .rp-splash-phone::before, .rp-splash-phone::after { content: ""; position: absolute; inset: 0; border-radius: 50%; border: 1px solid rgba(0,166,81,.5); animation: rp-ping 1.8s ease-out infinite; }
        .rp-splash-phone::after { animation-delay: .6s; }
        @keyframes rp-ping { 0% { transform: scale(.6); opacity: .8; } 100% { transform: scale(2.2); opacity: 0; } }
    </style>
</head>
<body class="@if($portal?->show_navbar) has-navbar @endif">
    <script>window.__rpSplashStart = Date.now();</script>
    <div class="rp-splash" id="rp-splash">
        <div class="rp-splash-phone">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="6" y="2" width="12" height="20" rx="2" stroke="#fff" stroke-width="1.6"/><path d="M10.5 18h3" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/><path d="M9 8.5h6M9 11.5h4" stroke="#00a651" stroke-width="1.6" stroke-linecap="round"/></svg>
        </div>
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
            <div class="crest rp-enter" style="--d:0s">
                @if($portal?->logo_url)
                    <img src="{{ $portal->logo_url }}" alt="{{ $tenant->company_name }}">
                @else
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M5 12.5C8.5 9 15.5 9 19 12.5" stroke="#fff" stroke-width="2" stroke-linecap="round"/><path d="M8 16C10 14 14 14 16 16" stroke="#fff" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="19" r="1.5" fill="#fff"/></svg>
                @endif
            </div>
            <h1 class="rp-enter" style="--d:.08s">{{ $tenant->company_name }}</h1>
            <div class="mpesa-badge rp-enter" style="--d:.16s">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><rect x="6" y="2" width="12" height="20" rx="2" stroke="#fff" stroke-width="2"/><path d="M10 18h4" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
                LIPA NA M-PESA
            </div>
            <p class="tag rp-enter" style="--d:.24s">Buy a plan, pay by M-Pesa, get online instantly</p>
            @if($tenant->support_phone)
                <p class="support rp-enter" style="--d:.32s">Call/Text: <b>{{ $tenant->support_phone }}</b></p>
            @endif
            @if($mac || $ip)
                <p class="device rp-enter" style="--d:.4s">{{ $mac }} {{ $ip }}</p>
            @endif
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
