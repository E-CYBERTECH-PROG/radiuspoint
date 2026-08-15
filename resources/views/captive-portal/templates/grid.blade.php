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
            --card-border: #111827;
            --card-radius: 4px;
            --card-shadow: none;
            --plan-radius: 2px;
            --btn-radius: 2px;
            --border: #111827;
            --divider: #111827;
        }
        body {
            background-color: #fafafa;
            background-image: linear-gradient(#e5e7eb 1px, transparent 1px), linear-gradient(90deg, #e5e7eb 1px, transparent 1px);
            background-size: 22px 22px;
        }

        .hero { background: #fff; border-bottom: 2px solid #111827; text-align: left; padding: 24px; }
        .hero .row { display: flex; align-items: center; gap: 12px; }
        .hero img { height: 36px; object-fit: contain; }
        .hero h1 { font-size: 18px; margin: 0; font-weight: 800; font-family: ui-monospace, "SF Mono", Menlo, monospace; }
        .hero p { font-size: 11px; margin: 6px 0 0; color: var(--text-muted); font-family: ui-monospace, "SF Mono", Menlo, monospace; text-transform: uppercase; letter-spacing: .06em; }

        .plan { border: 1.5px solid #111827; }
        .plan:hover { transform: none; border-color: var(--brand); box-shadow: 3px 3px 0 var(--brand); }
        .plans-grid { counter-reset: rp-plan; }
        .plan { counter-increment: rp-plan; }
        .plan::before { content: counter(rp-plan, decimal-leading-zero); position: absolute; top: 8px; right: 10px; font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: 10px; color: var(--text-muted); }
        .plan .meta, .plan .tier { font-family: ui-monospace, "SF Mono", Menlo, monospace; }
        .btn { border-radius: var(--btn-radius); }
        .action-card { border: 1.5px solid #111827; border-radius: 2px; }
        .action-card:not(:disabled):hover { box-shadow: 2px 2px 0 var(--brand); border-color: var(--brand); }

        .rp-splash { background: #fafafa; }
        .rp-splash-scan { width: 46px; height: 46px; border: 2px solid #111827; position: relative; overflow: hidden; background: #fff; }
        .rp-splash-scan::after { content: ""; position: absolute; left: 0; right: 0; height: 2px; background: var(--brand); animation: rp-scan 1.1s ease-in-out infinite; }
        @keyframes rp-scan { 0% { top: 0; } 100% { top: 100%; } }
    </style>
</head>
<body>
    <script>window.__rpSplashStart = Date.now();</script>
    <div class="rp-splash" id="rp-splash"><div class="rp-splash-scan"></div></div>

    <div class="card">
        <div class="hero">
            <div class="row">
                @if($portal?->logo_url)
                    <img src="{{ $portal->logo_url }}" alt="{{ $tenant->company_name }}">
                @else
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none"><path d="M5 12.5C8.5 9 15.5 9 19 12.5" stroke="#111827" stroke-width="2" stroke-linecap="round"/><path d="M8 16C10 14 14 14 16 16" stroke="#111827" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="19" r="1.5" fill="#111827"/></svg>
                @endif
                <div>
                    <h1>{{ $tenant->company_name }}</h1>
                </div>
            </div>
            <p>// wifi_access_point</p>
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
