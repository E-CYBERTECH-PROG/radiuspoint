<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'RadiusPoint') }} &mdash; Hotspot Billing for ISPs</title>
        <meta name="description" content="RadiusPoint helps ISPs provision Mikrotik routers, manage hotspot plans and customers, and run billing &mdash; all from one dashboard.">

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.scss', 'resources/js/app.js'])

        <style>
            /* === Marketing-page-only treatment — deliberately its own dark, gradient-forward
               look regardless of the visitor's theme preference (a bold brand choice for the
               public site), scoped here rather than the app's shared _custom.scss since none
               of this belongs in the authenticated admin UI. === */

            :root {
                --rp-void: #05070d;
                --rp-ink: #0a0e1a;
                --rp-panel: rgba(255,255,255,.04);
                --rp-border: rgba(255,255,255,.09);
                --rp-violet: #7367f0;
                --rp-violet-deep: #4c3fd6;
                --rp-magenta: #d946ef;
            }

            body { background: var(--rp-void); }

            /* Faint grain over every dark section — the difference between "flat gradient"
               and a surface that feels considered. Same trick, one tiny inline SVG turbulence
               filter tiled at low opacity. */
            .rp-grain {
                position: relative;
            }
            .rp-grain::before {
                content: '';
                position: absolute;
                inset: 0;
                pointer-events: none;
                opacity: .05;
                mix-blend-mode: overlay;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            }

            .rp-glow-orb {
                position: absolute;
                border-radius: 50%;
                filter: blur(80px);
                pointer-events: none;
            }

            .rp-gradient-text {
                background: linear-gradient(100deg, #fff 20%, var(--rp-violet) 60%, var(--rp-magenta) 100%);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }

            .rp-eyebrow {
                display: inline-flex;
                align-items: center;
                gap: .5rem;
                font-size: .8125rem;
                font-weight: 600;
                letter-spacing: .04em;
                text-transform: uppercase;
                color: rgba(255,255,255,.7);
            }
            .rp-eyebrow-dot {
                width: .5rem;
                height: .5rem;
                border-radius: 50%;
                background: var(--rp-violet);
                box-shadow: 0 0 12px 2px var(--rp-violet);
            }

            /* Glassmorphic card — the one surface treatment every section below reuses. */
            .rp-glass {
                background: var(--rp-panel);
                border: 1px solid var(--rp-border);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border-radius: 1.25rem;
            }

            /* Cursor-tracked spotlight — a soft radial highlight that follows the pointer
               inside each card, driven by --x/--y custom properties set from JS on
               pointermove. Cheap, no per-frame layout cost, reads as "alive" on hover. */
            .rp-spotlight {
                position: relative;
                overflow: hidden;
                transition: transform .3s ease, border-color .3s ease;
            }
            .rp-spotlight::after {
                content: '';
                position: absolute;
                inset: 0;
                background: radial-gradient(320px circle at var(--x, 50%) var(--y, 50%), rgba(115,103,240,.16), transparent 65%);
                opacity: 0;
                transition: opacity .3s ease;
                pointer-events: none;
            }
            .rp-spotlight:hover {
                transform: translateY(-4px);
                border-color: rgba(115,103,240,.35) !important;
            }
            .rp-spotlight:hover::after {
                opacity: 1;
            }

            #rp-marketing-header {
                background: rgba(5,7,13,.6);
                border-bottom: 1px solid transparent;
                backdrop-filter: blur(14px);
                -webkit-backdrop-filter: blur(14px);
                transition: border-color .2s ease, background .2s ease;
            }
            #rp-marketing-header.rp-scrolled {
                border-bottom-color: var(--rp-border);
                background: rgba(5,7,13,.85);
            }
            .rp-nav-link {
                color: rgba(255,255,255,.6);
                font-size: .875rem;
                font-weight: 500;
                text-decoration: none;
                transition: color .15s ease;
            }
            .rp-nav-link:hover { color: #fff; }

            .rp-hero-grid {
                background-image: linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
                                   linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
                background-size: 3rem 3rem;
                mask-image: radial-gradient(ellipse 70% 60% at 50% 0%, black, transparent 75%);
                -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 0%, black, transparent 75%);
            }

            .rp-h1 {
                font-size: clamp(2.75rem, 5.5vw, 4.75rem);
                font-weight: 800;
                letter-spacing: -.03em;
                line-height: 1.02;
            }
            .rp-h2 {
                font-size: clamp(1.75rem, 3vw, 2.5rem);
                font-weight: 700;
                letter-spacing: -.02em;
                color: #fff;
            }

            .rp-btn-glow {
                box-shadow: 0 0 0 1px rgba(115,103,240,.4), 0 8px 30px -8px rgba(115,103,240,.7);
                transition: box-shadow .25s ease, transform .25s ease;
            }
            .rp-btn-glow:hover {
                box-shadow: 0 0 0 1px rgba(115,103,240,.6), 0 12px 40px -8px rgba(115,103,240,.9);
                transform: translateY(-2px);
            }

            .rp-step-connector {
                position: absolute;
                top: 1.4rem;
                left: 60%;
                width: 80%;
                height: 1px;
                background: linear-gradient(to right, var(--rp-violet), transparent);
                opacity: .4;
            }

            .rp-muted { color: rgba(255,255,255,.55); }
            .rp-text-soft { color: rgba(255,255,255,.75); }

            @keyframes rp-pulse-ring {
                0% { box-shadow: 0 0 0 0 rgba(37,211,102,.5); }
                70% { box-shadow: 0 0 0 14px rgba(37,211,102,0); }
                100% { box-shadow: 0 0 0 0 rgba(37,211,102,0); }
            }
            .rp-pulse { animation: rp-pulse-ring 2.4s ease-out infinite; }
        </style>
    </head>
    <body>

        <header id="rp-marketing-header" class="sticky-top">
            <div class="container-xl py-3 d-flex align-items-center">
                <a href="/" class="d-flex align-items-center gap-2 text-decoration-none">
                    <x-application-logo class="text-primary" style="width:1.75rem;height:1.75rem" />
                    <span class="fw-bold text-white">RadiusPoint</span>
                </a>

                <nav class="d-none d-md-flex align-items-center gap-4 mx-auto">
                    <a href="#features" class="rp-nav-link">Features</a>
                    <a href="#hardware" class="rp-nav-link">Hardware</a>
                    <a href="#how-it-works" class="rp-nav-link">How It Works</a>
                </nav>

                <div class="d-flex align-items-center gap-2">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-sm rounded-pill px-3 rp-btn-glow">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rp-nav-link d-none d-sm-inline">
                            Log in
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-primary btn-sm rounded-pill px-3 rp-btn-glow">
                            Get Started
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="rp-grain position-relative overflow-hidden d-flex align-items-center" style="min-height:46rem;background:var(--rp-void)">
                <div class="rp-glow-orb" style="width:36rem;height:36rem;top:-14rem;left:-8rem;background:var(--rp-violet);opacity:.28"></div>
                <div class="rp-glow-orb" style="width:26rem;height:26rem;bottom:-8rem;right:-4rem;background:var(--rp-magenta);opacity:.14"></div>
                <div class="rp-hero-grid position-absolute top-0 start-0 w-100 h-100" aria-hidden="true"></div>

                {{-- Smoothly crossfading background: real Mikrotik hardware --}}
                <div class="position-absolute top-0 start-0 w-100 h-100">
                    <img data-hero-bg="0" src="{{ asset('images/hardware/rb4011.webp') }}" alt=""
                         class="position-absolute end-0 top-50 translate-middle-y d-none d-xl-block"
                         style="height:70%;width:auto;object-fit:contain;opacity:.9;transition:opacity 1.5s ease-in-out;filter:drop-shadow(0 30px 60px rgba(115,103,240,.35))" aria-hidden="true">
                    <img data-hero-bg="1" src="{{ asset('images/hardware/ccr1009.webp') }}" alt=""
                         class="position-absolute end-0 top-50 translate-middle-y d-none d-xl-block"
                         style="height:54%;width:auto;object-fit:contain;opacity:0;transition:opacity 1.5s ease-in-out;filter:drop-shadow(0 30px 60px rgba(115,103,240,.35))" aria-hidden="true">
                    <img data-hero-bg="2" src="{{ asset('images/hardware/rb951.webp') }}" alt=""
                         class="position-absolute end-0 top-50 translate-middle-y d-none d-xl-block"
                         style="height:54%;width:auto;object-fit:contain;opacity:0;transition:opacity 1.5s ease-in-out;filter:drop-shadow(0 30px 60px rgba(115,103,240,.35))" aria-hidden="true">
                    <div class="position-absolute top-0 end-0 h-100 d-none d-xl-block" style="width:46%;background:linear-gradient(to right, var(--rp-void), transparent 12%)"></div>
                </div>

                <div class="container-xl py-6 position-relative" style="z-index:1">
                    <div style="max-width:38rem">
                        <span class="rp-eyebrow"><span class="rp-eyebrow-dot"></span> Built for Mikrotik RouterOS</span>
                        <h1 class="rp-h1 text-white mt-3">
                            Run your hotspot ISP<br>from <span class="rp-gradient-text">one dashboard</span>
                        </h1>
                        <p class="rp-text-soft fs-4 mt-4" style="max-width:32rem">
                            Provision Mikrotik routers, sell hotspot plans, manage subscribers, and track
                            billing &mdash; without stitching together separate tools.
                        </p>
                        <div class="d-flex flex-wrap align-items-center gap-3 mt-4">
                            @guest
                                <a href="{{ route('register') }}" class="btn btn-primary btn-lg rounded-pill rp-btn-glow px-4">
                                    Get Started <i class="ti ti-arrow-right icon"></i>
                                </a>
                                <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg rounded-pill px-4">
                                    Log in
                                </a>
                            @else
                                <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg rounded-pill rp-btn-glow px-4">
                                    Go to Dashboard <i class="ti ti-arrow-right icon"></i>
                                </a>
                            @endguest
                        </div>

                        <div class="d-flex rp-glass mt-5 overflow-hidden" style="max-width:30rem">
                            <div class="flex-fill p-3 border-end" style="border-color:var(--rp-border) !important">
                                <div class="rp-muted small">Provisioning</div>
                                <div class="text-white fw-semibold">Zero-Touch</div>
                            </div>
                            <div class="flex-fill p-3 border-end" style="border-color:var(--rp-border) !important">
                                <div class="rp-muted small">Architecture</div>
                                <div class="text-white fw-semibold">Multi-Tenant</div>
                            </div>
                            <div class="flex-fill p-3">
                                <div class="rp-muted small">Onboarding</div>
                                <div class="text-white fw-semibold">Verified Signup</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Trust strip --}}
            <section class="border-top border-bottom" style="border-color:var(--rp-border) !important;background:var(--rp-ink)" data-reveal>
                <div class="container-xl py-3 d-flex flex-wrap align-items-center justify-content-center gap-4 small">
                    <span class="d-flex align-items-center gap-2 rp-text-soft"><i class="ti ti-server text-primary"></i>Mikrotik RouterOS Native</span>
                    <span class="d-none d-md-block" style="width:1px;height:1rem;background:var(--rp-border)"></span>
                    <span class="d-flex align-items-center gap-2 rp-text-soft"><i class="ti ti-building-skyscraper text-primary"></i>Multi-Tenant Architecture</span>
                    <span class="d-none d-md-block" style="width:1px;height:1rem;background:var(--rp-border)"></span>
                    <span class="d-flex align-items-center gap-2 rp-text-soft"><i class="ti ti-receipt text-primary"></i>Built-In Billing &amp; Transactions</span>
                    <span class="d-none d-md-block" style="width:1px;height:1rem;background:var(--rp-border)"></span>
                    <span class="d-flex align-items-center gap-2 rp-text-soft"><i class="ti ti-circle-check text-primary"></i>Email-Verified Onboarding</span>
                </div>
            </section>

            {{-- Dashboard preview --}}
            <section class="position-relative overflow-hidden" style="background:var(--rp-void)" data-reveal>
                <div class="rp-glow-orb" style="width:30rem;height:30rem;top:10%;right:-10rem;background:var(--rp-violet);opacity:.16"></div>
                <div class="container-xl py-6 position-relative">
                    <div class="row g-5 align-items-center">
                        <div class="col-lg-6 order-2 order-lg-1">
                            <span class="rp-eyebrow"><span class="rp-eyebrow-dot"></span> Command Center</span>
                            <h2 class="rp-h2 mt-3">See everything that matters, instantly</h2>
                            <p class="rp-muted mt-3 fs-5">
                                Today's income, active hotspot users, router health, and recent transactions
                                &mdash; all on one dashboard the moment you log in.
                            </p>
                            <ul class="list-unstyled mt-4 d-flex flex-column gap-3">
                                <li class="d-flex align-items-start gap-3">
                                    <span class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width:1.5rem;height:1.5rem;background:rgba(115,103,240,.15)"><i class="ti ti-check text-primary" style="font-size:.875rem"></i></span>
                                    <span class="rp-text-soft">Real-time income tracking, today and month-to-date</span>
                                </li>
                                <li class="d-flex align-items-start gap-3">
                                    <span class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width:1.5rem;height:1.5rem;background:rgba(115,103,240,.15)"><i class="ti ti-check text-primary" style="font-size:.875rem"></i></span>
                                    <span class="rp-text-soft">Router uptime monitoring across every site</span>
                                </li>
                                <li class="d-flex align-items-start gap-3">
                                    <span class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width:1.5rem;height:1.5rem;background:rgba(115,103,240,.15)"><i class="ti ti-check text-primary" style="font-size:.875rem"></i></span>
                                    <span class="rp-text-soft">A watchlist of hotspot users about to expire</span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-lg-6 order-1 order-lg-2">
                            <div class="position-relative">
                                <div class="rp-glow-orb" style="width:20rem;height:20rem;top:-3rem;left:-3rem;background:var(--rp-violet);opacity:.35"></div>
                                <div class="rp-glass overflow-hidden position-relative" style="z-index:1">
                                    <div class="px-3 py-2 d-flex align-items-center gap-2 border-bottom" style="border-color:var(--rp-border) !important">
                                        <span class="rounded-circle bg-red" style="width:.625rem;height:.625rem"></span>
                                        <span class="rounded-circle bg-yellow" style="width:.625rem;height:.625rem"></span>
                                        <span class="rounded-circle bg-green" style="width:.625rem;height:.625rem"></span>
                                        <span class="rp-muted small ms-2">radiuspoint.co.ke/dashboard</span>
                                    </div>
                                    {{-- Real screenshot of the dashboard against a demo tenant, not a mockup. --}}
                                    <img src="{{ asset('images/marketing/dashboard-preview.png') }}" alt="RadiusPoint dashboard showing income, recent transactions, and live customer activity" class="w-100 h-auto d-block">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Services — bento grid --}}
            <section id="features" class="border-top border-bottom" style="border-color:var(--rp-border) !important;background:var(--rp-ink)" data-reveal>
                <div class="container-xl py-6">
                    <div class="text-center mx-auto" style="max-width:34rem">
                        <span class="rp-eyebrow justify-content-center w-100"><span class="rp-eyebrow-dot"></span> What's Included</span>
                        <h2 class="rp-h2 mt-3">Everything you need to run a hotspot ISP</h2>
                    </div>

                    <div class="row g-3 mt-4">
                        <div class="col-md-7">
                            <div class="rp-glass rp-spotlight h-100 p-4 p-lg-5" data-spotlight>
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:3rem;height:3rem;background:linear-gradient(135deg, var(--rp-violet), var(--rp-violet-deep))"><i class="ti ti-router text-white fs-3"></i></span>
                                <h3 class="text-white fw-bold">Router Provisioning &amp; ZTP</h3>
                                <p class="rp-muted mb-0" style="max-width:26rem">Connect Mikrotik routers and walk through a guided zero-touch provisioning wizard &mdash; status checks and port configuration included.</p>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="rp-glass rp-spotlight h-100 p-4" data-spotlight>
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:3rem;height:3rem;background:rgba(6,182,212,.15)"><i class="ti ti-box text-info fs-3"></i></span>
                                <h3 class="text-white fw-bold">Plan &amp; Package Management</h3>
                                <p class="rp-muted mb-0">Custom pricing, duration, and speed limits, rolled out across your routers.</p>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="rp-glass rp-spotlight h-100 p-4" data-spotlight>
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:3rem;height:3rem;background:rgba(34,197,94,.15)"><i class="ti ti-users text-success fs-3"></i></span>
                                <h3 class="text-white fw-bold">Hotspot User Management</h3>
                                <p class="rp-muted mb-0">Onboard subscribers, assign plans, and track who's active across every router.</p>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="rp-glass rp-spotlight h-100 p-4 p-lg-5" data-spotlight>
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:3rem;height:3rem;background:rgba(245,158,11,.15)"><i class="ti ti-receipt text-warning fs-3"></i></span>
                                <h3 class="text-white fw-bold">Multi-Tenant Billing</h3>
                                <p class="rp-muted mb-0" style="max-width:26rem">Each ISP runs its own isolated workspace with its own routers, plans, customers, and transaction history.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Mikrotik hardware carousel --}}
            <section id="hardware" class="position-relative overflow-hidden" style="background:var(--rp-void)" data-reveal>
                <div class="rp-glow-orb" style="width:28rem;height:28rem;bottom:-10rem;left:-8rem;background:var(--rp-magenta);opacity:.14"></div>
                <div class="container-xl py-6 position-relative">
                    <div class="text-center mx-auto mb-5" style="max-width:36rem">
                        <span class="rp-eyebrow justify-content-center w-100"><span class="rp-eyebrow-dot"></span> Real Hardware Integration</span>
                        <h2 class="rp-h2 mt-3">Built for real Mikrotik hardware</h2>
                        <p class="rp-muted mt-3 fs-5">
                            RadiusPoint talks to your routers over the Mikrotik RouterOS API directly &mdash;
                            no manual configuration scripts to copy and paste by hand.
                        </p>
                    </div>

                    <div id="hw-carousel" class="rp-glass overflow-hidden">
                        <div class="row g-0 align-items-stretch">
                            <div class="col-lg-6 position-relative d-flex align-items-center justify-content-center p-5" style="min-height:18rem">
                                <div class="rp-glow-orb" style="width:16rem;height:16rem;top:50%;left:50%;transform:translate(-50%,-50%);background:var(--rp-violet);opacity:.25"></div>
                                <img data-hw-img="0" src="{{ asset('images/hardware/rb4011.webp') }}" alt="Mikrotik RB4011iGS+5HacQ2HnD-IN router" class="position-relative" style="max-height:14rem;width:auto;object-fit:contain;transition:all .7s ease-out;opacity:1;transform:translateX(0)">
                                <img data-hw-img="1" src="{{ asset('images/hardware/ccr1009.webp') }}" alt="Mikrotik CCR1009-7G-1C-1S+ Cloud Core Router" class="position-absolute" style="max-height:12rem;width:auto;object-fit:contain;transition:all .7s ease-out;opacity:0;transform:translateX(24px)">
                                <img data-hw-img="2" src="{{ asset('images/hardware/rb951.webp') }}" alt="Mikrotik RB951Ui-2HnD RouterBOARD" class="position-absolute" style="max-height:12rem;width:auto;object-fit:contain;transition:all .7s ease-out;opacity:0;transform:translateX(24px)">
                            </div>

                            <div class="col-lg-6 p-5 d-flex flex-column justify-content-center">
                                <div data-hw-slide="0" class="hw-slide">
                                    <span class="rp-eyebrow">Zero-Touch Provisioning</span>
                                    <h3 class="text-white fw-bold mt-2">RB4011iGS+5HacQ2HnD-IN</h3>
                                    <p class="rp-muted">Paste one script into the terminal and RadiusPoint handles the rest &mdash; WireGuard tunnel, API access, and RADIUS integration configured automatically. No manual CLI work.</p>
                                </div>
                                <div data-hw-slide="1" class="hw-slide d-none">
                                    <span class="rp-eyebrow">Built to Scale</span>
                                    <h3 class="text-white fw-bold mt-2">CCR1009-7G-1C-1S+</h3>
                                    <p class="rp-muted">Cloud Core Routers handle high-throughput, multi-tenant deployments &mdash; built for ISPs running PPPoE at scale across hundreds of subscribers.</p>
                                </div>
                                <div data-hw-slide="2" class="hw-slide d-none">
                                    <span class="rp-eyebrow">Hotspot-Ready</span>
                                    <h3 class="text-white fw-bold mt-2">RB951Ui-2HnD</h3>
                                    <p class="rp-muted">A compact RouterBOARD built for hotspot deployments. Plug it in, run the provisioning wizard, and start selling packages in minutes.</p>
                                </div>

                                <div class="d-flex align-items-center gap-2 mt-3">
                                    <button type="button" data-hw-dot="0" class="hw-dot btn p-0 rounded-pill bg-primary" style="width:1.5rem;height:.375rem" aria-label="Show RB4011 slide"></button>
                                    <button type="button" data-hw-dot="1" class="hw-dot btn p-0 rounded-pill" style="width:.375rem;height:.375rem;background:var(--rp-border)" aria-label="Show CCR1009 slide"></button>
                                    <button type="button" data-hw-dot="2" class="hw-dot btn p-0 rounded-pill" style="width:.375rem;height:.375rem;background:var(--rp-border)" aria-label="Show RB951 slide"></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row row-cols-1 row-cols-sm-3 g-3 mt-4">
                        <div class="col d-flex align-items-start gap-2">
                            <i class="ti ti-circle-check text-success mt-1"></i>
                            <span class="rp-text-soft">Guided zero-touch provisioning wizard</span>
                        </div>
                        <div class="col d-flex align-items-start gap-2">
                            <i class="ti ti-circle-check text-success mt-1"></i>
                            <span class="rp-text-soft">Live connection status checks per router</span>
                        </div>
                        <div class="col d-flex align-items-start gap-2">
                            <i class="ti ti-circle-check text-success mt-1"></i>
                            <span class="rp-text-soft">Port selection and configuration from the dashboard</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- How it works --}}
            <section id="how-it-works" class="border-top border-bottom" style="border-color:var(--rp-border) !important;background:var(--rp-ink)" data-reveal>
                <div class="container-xl py-6">
                    <div class="text-center mx-auto" style="max-width:30rem">
                        <span class="rp-eyebrow justify-content-center w-100"><span class="rp-eyebrow-dot"></span> Onboarding</span>
                        <h2 class="rp-h2 mt-3">Joining is simple</h2>
                        <p class="rp-muted mt-2">We review every new ISP account to keep the platform trustworthy.</p>
                    </div>

                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4 mt-3 text-center">
                        @foreach([
                            ['n' => 1, 'title' => 'Sign up', 'body' => 'Tell us about your company and create your account.'],
                            ['n' => 2, 'title' => 'Verify your email', 'body' => 'Confirm your email address using the link we send you.'],
                            ['n' => 3, 'title' => 'Get approved', 'body' => 'Our team reviews your application and approves your account.'],
                            ['n' => 4, 'title' => 'Launch', 'body' => 'Log in, provision your routers, and start selling hotspot plans.'],
                        ] as $step)
                            <div class="col position-relative">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white fw-bold" style="width:2.75rem;height:2.75rem;background:linear-gradient(135deg, var(--rp-violet), var(--rp-violet-deep));box-shadow:0 0 0 6px var(--rp-ink), 0 0 24px -4px var(--rp-violet)">{{ $step['n'] }}</span>
                                @if($step['n'] < 4)
                                    <span class="rp-step-connector d-none d-lg-block" aria-hidden="true"></span>
                                @endif
                                <h3 class="text-white fw-bold mt-3">{{ $step['title'] }}</h3>
                                <p class="rp-muted small">{{ $step['body'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- CTA --}}
            @guest
                <section class="position-relative overflow-hidden rp-grain" style="background:var(--rp-void)" data-reveal>
                    <div class="rp-glow-orb" style="width:32rem;height:32rem;top:50%;left:50%;transform:translate(-50%,-50%);background:linear-gradient(135deg, var(--rp-violet), var(--rp-magenta));opacity:.28"></div>
                    <div class="container-xl py-6 text-center position-relative">
                        <h2 class="rp-h2" style="font-size:clamp(1.75rem, 4vw, 3rem)">Ready to get your ISP online?</h2>
                        <p class="rp-muted mt-2 fs-5">Create your account today &mdash; it only takes a couple of minutes.</p>
                        <a href="{{ route('register') }}" class="btn btn-primary btn-lg rounded-pill rp-btn-glow mt-4 px-4">
                            Get Started <i class="ti ti-arrow-right icon"></i>
                        </a>
                    </div>
                </section>
            @endguest
        </main>

        <footer class="border-top" style="border-color:var(--rp-border) !important;background:var(--rp-ink)">
            <div class="container-xl py-4 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 small">
                <div class="d-flex align-items-center gap-2 rp-muted">
                    <x-application-logo class="text-muted" style="width:1.25rem;height:1.25rem" />
                    <span>&copy; {{ date('Y') }} RadiusPoint. All rights reserved.</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    @guest
                        <a href="{{ route('login') }}" class="rp-nav-link">Log in</a>
                        <a href="{{ route('register') }}" class="rp-nav-link">Get Started</a>
                    @endguest
                </div>
            </div>
        </footer>

        <div class="position-fixed bottom-0 end-0 d-flex flex-column gap-3 p-4" style="z-index:1030">
            <a href="https://wa.me/254790738021" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"
               class="rp-pulse d-flex align-items-center justify-content-center rounded-circle bg-green text-white shadow" style="width:3.5rem;height:3.5rem">
                <i class="ti ti-brand-whatsapp fs-2"></i>
            </a>
            <a href="tel:+254790738021" aria-label="Call us"
               class="d-flex align-items-center justify-content-center rounded-circle text-white shadow" style="width:3.5rem;height:3.5rem;background:linear-gradient(135deg, var(--rp-violet), var(--rp-violet-deep))">
                <i class="ti ti-phone fs-2"></i>
            </a>
        </div>

        <script>
            // Header: subtle shadow once the page has scrolled past the hero, so the
            // translucent/blurred bar reads as "floating" rather than blending into content.
            (function () {
                var header = document.getElementById('rp-marketing-header');
                if (!header) return;

                function sync() {
                    header.classList.toggle('rp-scrolled', window.scrollY > 8);
                }

                document.addEventListener('scroll', sync, { passive: true });
                sync();
            })();

            // Cursor-tracked spotlight on the bento feature cards — sets --x/--y so the
            // radial-gradient highlight in .rp-spotlight::after follows the pointer.
            (function () {
                document.querySelectorAll('[data-spotlight]').forEach(function (card) {
                    card.addEventListener('pointermove', function (e) {
                        var rect = card.getBoundingClientRect();
                        card.style.setProperty('--x', (e.clientX - rect.left) + 'px');
                        card.style.setProperty('--y', (e.clientY - rect.top) + 'px');
                    });
                });
            })();

            // Hero background: smooth crossfade between real Mikrotik hardware photos
            (function () {
                var imgs = document.querySelectorAll('[data-hero-bg]');
                if (!imgs.length) return;

                var current = 0;
                function next() {
                    var nextIndex = (current + 1) % imgs.length;
                    imgs[current].style.opacity = '0';
                    imgs[nextIndex].style.opacity = '.9';
                    current = nextIndex;
                }

                setInterval(next, 4500);
            })();

            // Scroll-reveal: fade + rise sections into view as the user scrolls.
            // Styles are set inline (not via classes) so they always win the cascade.
            (function () {
                var revealEls = document.querySelectorAll('[data-reveal]');

                revealEls.forEach(function (el) {
                    el.style.transition = 'opacity .7s ease, transform .7s ease';
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(24px)';
                });

                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.12 });

                revealEls.forEach(function (el) { io.observe(el); });
            })();

            // Mikrotik hardware carousel
            (function () {
                var carousel = document.getElementById('hw-carousel');
                if (!carousel) return;

                var slides = carousel.querySelectorAll('[data-hw-slide]');
                var imgs = carousel.querySelectorAll('[data-hw-img]');
                var dots = carousel.querySelectorAll('[data-hw-dot]');
                var current = 0;
                var timer;

                function show(index) {
                    slides.forEach(function (s, i) { s.classList.toggle('d-none', i !== index); });
                    imgs.forEach(function (img, i) {
                        img.style.opacity = i === index ? '1' : '0';
                        img.style.transform = i === index ? 'translateX(0)' : 'translateX(24px)';
                    });
                    dots.forEach(function (d, i) {
                        d.style.width = i === index ? '1.5rem' : '.375rem';
                        d.style.background = i === index ? 'var(--tblr-primary)' : 'var(--rp-border)';
                    });
                    current = index;
                }

                function next() { show((current + 1) % slides.length); }

                function startAuto() { timer = setInterval(next, 5000); }
                function stopAuto() { clearInterval(timer); }

                dots.forEach(function (dot, i) {
                    dot.addEventListener('click', function () {
                        show(i);
                        stopAuto();
                        startAuto();
                    });
                });

                carousel.addEventListener('mouseenter', stopAuto);
                carousel.addEventListener('mouseleave', startAuto);

                show(0);
                startAuto();
            })();
        </script>
    </body>
</html>
