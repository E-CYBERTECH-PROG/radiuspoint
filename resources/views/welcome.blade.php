<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'RadiusPoint') }} &mdash; Hotspot Billing for ISPs</title>
        <meta name="description" content="RadiusPoint helps ISPs provision Mikrotik routers, manage hotspot plans and customers, and run billing &mdash; all from one dashboard.">

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.scss', 'resources/js/app.js'])
    </head>
    <body class="bg-body">

        <header class="navbar navbar-expand-md navbar-light border-bottom sticky-top bg-body">
            <div class="container-xl">
                <a href="/" class="navbar-brand d-flex align-items-center gap-2">
                    <x-application-logo class="text-primary" style="width:2rem;height:2rem" />
                    <span class="fw-bold">RadiusPoint</span>
                </a>

                <div class="navbar-nav flex-row gap-2 ms-auto">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-sm">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-link btn-sm">
                            Log in
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-primary btn-sm">
                            Get Started
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="bg-dark position-relative overflow-hidden d-flex align-items-center" style="min-height:38rem">
                {{-- Smoothly crossfading background: real Mikrotik hardware --}}
                <div class="position-absolute top-0 start-0 w-100 h-100">
                    <img data-hero-bg="0" src="{{ asset('images/hardware/rb4011.webp') }}" alt=""
                         class="position-absolute end-0 top-50 translate-middle-y d-none d-lg-block"
                         style="height:80%;width:auto;object-fit:contain;opacity:1;transition:opacity 1.5s ease-in-out" aria-hidden="true">
                    <img data-hero-bg="1" src="{{ asset('images/hardware/ccr1009.webp') }}" alt=""
                         class="position-absolute end-0 top-50 translate-middle-y d-none d-lg-block"
                         style="height:62%;width:auto;object-fit:contain;opacity:0;transition:opacity 1.5s ease-in-out" aria-hidden="true">
                    <img data-hero-bg="2" src="{{ asset('images/hardware/rb951.webp') }}" alt=""
                         class="position-absolute end-0 top-50 translate-middle-y d-none d-lg-block"
                         style="height:62%;width:auto;object-fit:contain;opacity:0;transition:opacity 1.5s ease-in-out" aria-hidden="true">

                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background:linear-gradient(to right, rgba(17,24,39,1), rgba(17,24,39,.95) 55%, rgba(17,24,39,.4))"></div>
                </div>

                <div class="container-xl py-6 position-relative" style="z-index:1">
                    <div style="max-width:36rem">
                        <span class="badge bg-primary-lt">
                            Built for Mikrotik RouterOS
                        </span>
                        <h1 class="display-4 fw-bold text-white mt-3" style="line-height:1.1">
                            Run your hotspot ISP business from one dashboard
                        </h1>
                        <p class="text-white-50 fs-3 mt-3">
                            RadiusPoint gives internet service providers everything they need to provision
                            Mikrotik routers, sell hotspot plans, manage subscribers, and track billing
                            &mdash; without stitching together separate tools.
                        </p>
                        <div class="d-flex align-items-center gap-3 mt-4">
                            @guest
                                <a href="{{ route('register') }}" class="btn btn-primary btn-lg rounded-pill">
                                    Get Started
                                </a>
                                <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg rounded-pill">
                                    Log in
                                </a>
                            @else
                                <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg rounded-pill">
                                    Go to Dashboard
                                </a>
                            @endguest
                        </div>

                        <div class="row g-4 mt-4" style="max-width:28rem">
                            <div class="col-4">
                                <div class="text-white-50 small">Provisioning</div>
                                <div class="text-white fw-semibold">Zero-Touch</div>
                            </div>
                            <div class="col-4">
                                <div class="text-white-50 small">Architecture</div>
                                <div class="text-white fw-semibold">Multi-Tenant</div>
                            </div>
                            <div class="col-4">
                                <div class="text-white-50 small">Onboarding</div>
                                <div class="text-white fw-semibold">Verified Signup</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Trust strip --}}
            <section class="border-bottom bg-body-secondary" data-reveal>
                <div class="container-xl py-3 d-flex flex-wrap align-items-center justify-content-center gap-4 text-muted small">
                    <span class="d-flex align-items-center gap-2"><i class="ti ti-server text-primary"></i>Mikrotik RouterOS Native</span>
                    <span class="d-flex align-items-center gap-2"><i class="ti ti-building-skyscraper text-primary"></i>Multi-Tenant Architecture</span>
                    <span class="d-flex align-items-center gap-2"><i class="ti ti-receipt text-primary"></i>Built-In Billing &amp; Transactions</span>
                    <span class="d-flex align-items-center gap-2"><i class="ti ti-circle-check text-primary"></i>Email-Verified Onboarding</span>
                </div>
            </section>

            {{-- Dashboard preview --}}
            <section class="container-xl py-6" data-reveal>
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6 order-2 order-lg-1">
                        <span class="text-primary fw-semibold small">Command Center</span>
                        <h2 class="mt-2">See everything that matters, instantly</h2>
                        <p class="text-muted mt-3">
                            Today's income, active hotspot users, router health, and recent transactions
                            &mdash; all on one dashboard the moment you log in.
                        </p>
                        <ul class="list-unstyled mt-4 d-flex flex-column gap-2">
                            <li class="d-flex align-items-start gap-2">
                                <i class="ti ti-circle-check text-success mt-1"></i>
                                Real-time income tracking, today and month-to-date
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="ti ti-circle-check text-success mt-1"></i>
                                Router uptime monitoring across every site
                            </li>
                            <li class="d-flex align-items-start gap-2">
                                <i class="ti ti-circle-check text-success mt-1"></i>
                                A watchlist of hotspot users about to expire
                            </li>
                        </ul>
                    </div>
                    <div class="col-lg-6 order-1 order-lg-2">
                        <div class="card shadow-lg overflow-hidden">
                            <div class="bg-body-secondary px-3 py-2 d-flex align-items-center gap-2 border-bottom">
                                <span class="rounded-circle bg-red" style="width:.625rem;height:.625rem"></span>
                                <span class="rounded-circle bg-yellow" style="width:.625rem;height:.625rem"></span>
                                <span class="rounded-circle bg-green" style="width:.625rem;height:.625rem"></span>
                                <span class="text-muted small ms-2">radiuspoint.co.ke/dashboard</span>
                            </div>
                            {{-- Real screenshot of the dashboard against a demo tenant, not a mockup. --}}
                            <img src="{{ asset('images/marketing/dashboard-preview.png') }}" alt="RadiusPoint dashboard showing income, recent transactions, and live customer activity" class="w-100 h-auto d-block">
                        </div>
                    </div>
                </div>
            </section>

            {{-- Services --}}
            <section class="bg-body-secondary border-top border-bottom" data-reveal>
                <div class="container-xl py-6">
                    <h2 class="text-center">Everything you need to run a hotspot ISP</h2>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mt-4">
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <span class="avatar bg-primary-lt mb-3"><i class="ti ti-router"></i></span>
                                    <h3 class="card-title">Router Provisioning &amp; ZTP</h3>
                                    <p class="text-muted small mb-0">Connect Mikrotik routers and walk through a guided zero-touch provisioning wizard &mdash; status checks and port configuration included.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <span class="avatar bg-primary-lt mb-3"><i class="ti ti-box"></i></span>
                                    <h3 class="card-title">Plan &amp; Package Management</h3>
                                    <p class="text-muted small mb-0">Build hotspot data and time packages with custom pricing, duration, and speed limits, and roll them out across your routers.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <span class="avatar bg-primary-lt mb-3"><i class="ti ti-users"></i></span>
                                    <h3 class="card-title">Hotspot User Management</h3>
                                    <p class="text-muted small mb-0">Onboard subscribers, assign them to plans, and keep track of who's active across every router you manage.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <span class="avatar bg-primary-lt mb-3"><i class="ti ti-receipt"></i></span>
                                    <h3 class="card-title">Multi-Tenant Billing</h3>
                                    <p class="text-muted small mb-0">Each ISP runs its own isolated workspace with its own routers, plans, customers, and transaction history.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Mikrotik hardware carousel --}}
            <section class="container-xl py-6" data-reveal>
                <div class="text-center mx-auto mb-5" style="max-width:36rem">
                    <span class="text-primary fw-semibold small">Real Hardware Integration</span>
                    <h2 class="mt-2">Built for real Mikrotik hardware</h2>
                    <p class="text-muted mt-3">
                        RadiusPoint talks to your routers over the Mikrotik RouterOS API directly &mdash;
                        no manual configuration scripts to copy and paste by hand.
                    </p>
                </div>

                <div id="hw-carousel" class="card overflow-hidden">
                    <div class="row g-0 align-items-stretch">
                        <div class="col-lg-6 position-relative bg-body d-flex align-items-center justify-content-center p-5" style="min-height:18rem">
                            <img data-hw-img="0" src="{{ asset('images/hardware/rb4011.webp') }}" alt="Mikrotik RB4011iGS+5HacQ2HnD-IN router" class="position-absolute" style="max-height:14rem;width:auto;object-fit:contain;transition:all .7s ease-out;opacity:1;transform:translateX(0)">
                            <img data-hw-img="1" src="{{ asset('images/hardware/ccr1009.webp') }}" alt="Mikrotik CCR1009-7G-1C-1S+ Cloud Core Router" class="position-absolute" style="max-height:12rem;width:auto;object-fit:contain;transition:all .7s ease-out;opacity:0;transform:translateX(24px)">
                            <img data-hw-img="2" src="{{ asset('images/hardware/rb951.webp') }}" alt="Mikrotik RB951Ui-2HnD RouterBOARD" class="position-absolute" style="max-height:12rem;width:auto;object-fit:contain;transition:all .7s ease-out;opacity:0;transform:translateX(24px)">
                        </div>

                        <div class="col-lg-6 p-5 d-flex flex-column justify-content-center">
                            <div data-hw-slide="0" class="hw-slide">
                                <span class="text-uppercase text-primary fw-bold small">Zero-Touch Provisioning</span>
                                <h3 class="mt-2">RB4011iGS+5HacQ2HnD-IN</h3>
                                <p class="text-muted">Paste one script into the terminal and RadiusPoint handles the rest &mdash; WireGuard tunnel, API access, and RADIUS integration configured automatically. No manual CLI work.</p>
                            </div>
                            <div data-hw-slide="1" class="hw-slide d-none">
                                <span class="text-uppercase text-primary fw-bold small">Built to Scale</span>
                                <h3 class="mt-2">CCR1009-7G-1C-1S+</h3>
                                <p class="text-muted">Cloud Core Routers handle high-throughput, multi-tenant deployments &mdash; built for ISPs running PPPoE at scale across hundreds of subscribers.</p>
                            </div>
                            <div data-hw-slide="2" class="hw-slide d-none">
                                <span class="text-uppercase text-primary fw-bold small">Hotspot-Ready</span>
                                <h3 class="mt-2">RB951Ui-2HnD</h3>
                                <p class="text-muted">A compact RouterBOARD built for hotspot deployments. Plug it in, run the provisioning wizard, and start selling packages in minutes.</p>
                            </div>

                            <div class="d-flex align-items-center gap-2 mt-3">
                                <button type="button" data-hw-dot="0" class="hw-dot btn p-0 rounded-circle bg-primary" style="width:.625rem;height:.625rem" aria-label="Show RB4011 slide"></button>
                                <button type="button" data-hw-dot="1" class="hw-dot btn p-0 rounded-circle bg-secondary" style="width:.625rem;height:.625rem" aria-label="Show CCR1009 slide"></button>
                                <button type="button" data-hw-dot="2" class="hw-dot btn p-0 rounded-circle bg-secondary" style="width:.625rem;height:.625rem" aria-label="Show RB951 slide"></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row row-cols-1 row-cols-sm-3 g-3 mt-4">
                    <div class="col d-flex align-items-start gap-2">
                        <i class="ti ti-circle-check text-success mt-1"></i>
                        <span>Guided zero-touch provisioning wizard</span>
                    </div>
                    <div class="col d-flex align-items-start gap-2">
                        <i class="ti ti-circle-check text-success mt-1"></i>
                        <span>Live connection status checks per router</span>
                    </div>
                    <div class="col d-flex align-items-start gap-2">
                        <i class="ti ti-circle-check text-success mt-1"></i>
                        <span>Port selection and configuration from the dashboard</span>
                    </div>
                </div>
            </section>

            {{-- How it works --}}
            <section class="bg-body-secondary border-top border-bottom" data-reveal>
                <div class="container-xl py-6">
                    <h2 class="text-center">Joining is simple</h2>
                    <p class="text-center text-muted">We review every new ISP account to keep the platform trustworthy.</p>

                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4 mt-3 text-center">
                        <div class="col">
                            <span class="avatar avatar-sm rounded-circle bg-primary text-white fw-bold">1</span>
                            <h3 class="mt-3">Sign up</h3>
                            <p class="text-muted small">Tell us about your company and create your account.</p>
                        </div>
                        <div class="col">
                            <span class="avatar avatar-sm rounded-circle bg-primary text-white fw-bold">2</span>
                            <h3 class="mt-3">Verify your email</h3>
                            <p class="text-muted small">Confirm your email address using the link we send you.</p>
                        </div>
                        <div class="col">
                            <span class="avatar avatar-sm rounded-circle bg-primary text-white fw-bold">3</span>
                            <h3 class="mt-3">Get approved</h3>
                            <p class="text-muted small">Our team reviews your application and approves your account.</p>
                        </div>
                        <div class="col">
                            <span class="avatar avatar-sm rounded-circle bg-primary text-white fw-bold">4</span>
                            <h3 class="mt-3">Launch</h3>
                            <p class="text-muted small">Log in, provision your routers, and start selling hotspot plans.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- CTA --}}
            @guest
                <section class="bg-primary" data-reveal>
                    <div class="container-xl py-5 text-center">
                        <h2 class="text-white">Ready to get your ISP online?</h2>
                        <p class="text-white-50 mt-2">Create your account today &mdash; it only takes a couple of minutes.</p>
                        <a href="{{ route('register') }}" class="btn btn-light mt-3">
                            Get Started
                        </a>
                    </div>
                </section>
            @endguest
        </main>

        <footer class="border-top">
            <div class="container-xl py-4 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 text-muted small">
                <div class="d-flex align-items-center gap-2">
                    <x-application-logo class="text-muted" style="width:1.25rem;height:1.25rem" />
                    <span>&copy; {{ date('Y') }} RadiusPoint. All rights reserved.</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    @guest
                        <a href="{{ route('login') }}" class="text-muted">Log in</a>
                        <a href="{{ route('register') }}" class="text-muted">Get Started</a>
                    @endguest
                </div>
            </div>
        </footer>

        <div class="position-fixed bottom-0 end-0 d-flex flex-column gap-3 p-4" style="z-index:1030">
            <a href="https://wa.me/254790738021" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"
               class="d-flex align-items-center justify-content-center rounded-circle bg-green text-white shadow" style="width:3.5rem;height:3.5rem">
                <i class="ti ti-brand-whatsapp fs-2"></i>
            </a>
            <a href="tel:+254790738021" aria-label="Call us"
               class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white shadow" style="width:3.5rem;height:3.5rem">
                <i class="ti ti-phone fs-2"></i>
            </a>
        </div>

        <script>
            // Hero background: smooth crossfade between real Mikrotik hardware photos
            (function () {
                var imgs = document.querySelectorAll('[data-hero-bg]');
                if (!imgs.length) return;

                var current = 0;
                function next() {
                    var nextIndex = (current + 1) % imgs.length;
                    imgs[current].style.opacity = '0';
                    imgs[nextIndex].style.opacity = '1';
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
                        d.classList.toggle('bg-primary', i === index);
                        d.classList.toggle('bg-secondary', i !== index);
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
