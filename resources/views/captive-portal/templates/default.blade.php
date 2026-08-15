<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $tenant->company_name }} — WiFi</title>
    {{--
        Self-hosted only (inline CSS, inline SVG icons, vanilla JS), no CDN assets. Pre-auth,
        RouterOS's walled garden only allows the one domain configured in
        RouterController::provisionCaptivePortal, so any CDN request fails.
    --}}
    @include('captive-portal.partials._styles')
    <style>
        .hero { background: var(--brand); color: #fff; text-align: center; padding: 28px 24px; }
        .hero img { height: 48px; margin: 0 auto 8px; display: block; object-fit: contain; }
        .hero h1 { font-size: 19px; margin: 6px 0 2px; }
        .hero p { font-size: 13px; margin: 0; opacity: .85; }

        .rp-splash { background: #fff; }
        .rp-splash-ring { width: 44px; height: 44px; border: 4px solid #e5e7eb; border-top-color: var(--brand); border-radius: 50%; animation: rp-spin .8s linear infinite; }
    </style>
</head>
<body>
    <script>window.__rpSplashStart = Date.now();</script>
    <div class="rp-splash" id="rp-splash"><div class="rp-splash-ring"></div></div>

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
