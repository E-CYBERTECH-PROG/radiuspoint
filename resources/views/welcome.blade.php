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

        @vite(['resources/css/app.css', 'resources/js/app.js'])

    </head>
    <body class="font-sans text-gray-900 antialiased bg-white">

        <header class="sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-gray-100">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-14 sm:h-16 flex items-center justify-between">
                <a href="/" class="flex items-center gap-1.5 sm:gap-2 min-w-0">
                    <x-application-logo class="h-7 w-7 sm:h-8 sm:w-8 text-indigo-600 shrink-0" />
                    <span class="font-bold text-base sm:text-lg tracking-tight truncate">RadiusPoint</span>
                </a>

                <nav class="flex items-center gap-2 sm:gap-4 shrink-0">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="inline-block px-3 py-1.5 sm:px-5 sm:py-2 bg-indigo-600 text-white text-xs sm:text-sm font-medium rounded-md hover:bg-indigo-700 whitespace-nowrap">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-xs sm:text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">
                            Log in
                        </a>
                        <a href="{{ route('register') }}" class="inline-block px-3 py-1.5 sm:px-5 sm:py-2 bg-indigo-600 text-white text-xs sm:text-sm font-medium rounded-md hover:bg-indigo-700 whitespace-nowrap">
                            Get Started
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="relative bg-gray-900 overflow-hidden min-h-[620px] flex items-center">
                {{-- Smoothly crossfading background: real Mikrotik hardware --}}
                <div class="absolute inset-0">
                    <img data-hero-bg="0" src="{{ asset('images/hardware/rb4011.webp') }}" alt=""
                         class="absolute right-0 lg:right-10 top-1/2 -translate-y-1/2 h-[80%] w-auto object-contain transition-opacity duration-[1500ms] ease-in-out"
                         style="opacity: 1;" aria-hidden="true">
                    <img data-hero-bg="1" src="{{ asset('images/hardware/ccr1009.webp') }}" alt=""
                         class="absolute right-0 lg:right-10 top-1/2 -translate-y-1/2 h-[62%] w-auto object-contain transition-opacity duration-[1500ms] ease-in-out"
                         style="opacity: 0;" aria-hidden="true">
                    <img data-hero-bg="2" src="{{ asset('images/hardware/rb951.webp') }}" alt=""
                         class="absolute right-0 lg:right-10 top-1/2 -translate-y-1/2 h-[62%] w-auto object-contain transition-opacity duration-[1500ms] ease-in-out"
                         style="opacity: 0;" aria-hidden="true">

                    <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-gray-900/95 to-gray-900/40"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(79,70,229,0.25),transparent_45%)]"></div>
                </div>

                <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 relative z-10 w-full">
                    <div class="max-w-xl">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-300 text-xs font-medium ring-1 ring-indigo-400/30">
                            Built for Mikrotik RouterOS
                        </span>
                        <h1 class="mt-5 text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.05]">
                            Run your hotspot ISP business from one dashboard
                        </h1>
                        <p class="mt-6 text-lg text-gray-300 max-w-xl">
                            RadiusPoint gives internet service providers everything they need to provision
                            Mikrotik routers, sell hotspot plans, manage subscribers, and track billing
                            &mdash; without stitching together separate tools.
                        </p>
                        <div class="mt-8 flex items-center gap-4">
                            @guest
                                <a href="{{ route('register') }}" class="inline-block px-6 py-3 bg-indigo-600 text-white font-semibold rounded-full hover:bg-indigo-500 hover:scale-105 shadow-lg shadow-indigo-600/30 transition-all">
                                    Get Started
                                </a>
                                <a href="{{ route('login') }}" class="inline-block px-6 py-3 border border-gray-600 text-gray-200 font-medium rounded-full hover:border-gray-400 hover:text-white transition-colors">
                                    Log in
                                </a>
                            @else
                                <a href="{{ url('/dashboard') }}" class="inline-block px-6 py-3 bg-indigo-600 text-white font-semibold rounded-full hover:bg-indigo-500 hover:scale-105 shadow-lg shadow-indigo-600/30 transition-all">
                                    Go to Dashboard
                                </a>
                            @endguest
                        </div>

                        <dl class="mt-10 grid grid-cols-3 gap-6 max-w-md">
                            <div>
                                <dt class="text-xs text-gray-400">Provisioning</dt>
                                <dd class="text-sm font-semibold text-white">Zero-Touch</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400">Architecture</dt>
                                <dd class="text-sm font-semibold text-white">Multi-Tenant</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400">Onboarding</dt>
                                <dd class="text-sm font-semibold text-white">Verified Signup</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>

            {{-- Trust strip --}}
            <section class="border-y border-gray-100 bg-gray-50" data-reveal>
                <div class="max-w-6xl mx-auto px-6 lg:px-8 py-6 flex flex-wrap items-center justify-center gap-x-10 gap-y-3 text-sm text-gray-500">
                    <span class="flex items-center gap-2"><svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" /></svg>Mikrotik RouterOS Native</span>
                    <span class="flex items-center gap-2"><svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21" /></svg>Multi-Tenant Architecture</span>
                    <span class="flex items-center gap-2"><svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>Built-In Billing &amp; Transactions</span>
                    <span class="flex items-center gap-2"><svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>Email-Verified Onboarding</span>
                </div>
            </section>

            {{-- Dashboard preview --}}
            <section class="max-w-6xl mx-auto px-6 lg:px-8 py-20" data-reveal>
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div class="order-2 lg:order-1">
                        <span class="text-sm font-semibold text-indigo-600">Command Center</span>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-gray-900">See everything that matters, instantly</h2>
                        <p class="mt-4 text-gray-600">
                            Today's income, active hotspot users, router health, and recent transactions
                            &mdash; all on one dashboard the moment you log in.
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-gray-700">
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                Real-time income tracking, today and month-to-date
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                Router uptime monitoring across every site
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                A watchlist of hotspot users about to expire
                            </li>
                        </ul>
                    </div>
                    <div class="order-1 lg:order-2">
                        <div class="rounded-xl shadow-2xl ring-1 ring-gray-900/10 overflow-hidden">
                            <div class="bg-gray-100 px-4 py-3 flex items-center gap-1.5 border-b border-gray-200">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-400"></span>
                                <span class="ml-3 text-xs text-gray-400">radiuspoint.co.ke/dashboard</span>
                            </div>
                            {{-- A real screenshot of the actual product dashboard, rendered against a
                                 dedicated internal demo tenant with realistic-but-fake data — not real
                                 customer phone numbers/revenue, and not a hand-built HTML mockup. --}}
                            <img src="{{ asset('images/marketing/dashboard-preview.png') }}" alt="RadiusPoint dashboard showing income, recent transactions, and live customer activity" class="w-full h-auto block">
                        </div>
                    </div>
                </div>
            </section>

            {{-- Services --}}
            <section class="bg-gray-50 border-y border-gray-100" data-reveal>
                <div class="max-w-6xl mx-auto px-6 lg:px-8 py-20">
                    <h2 class="text-2xl font-semibold text-center text-gray-900">Everything you need to run a hotspot ISP</h2>
                    <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-white p-6 rounded-lg border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                            <div class="h-10 w-10 rounded-md bg-indigo-50 flex items-center justify-center text-indigo-600 mb-4">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21M6.75 6.75h10.5v10.5H6.75V6.75Z" /></svg>
                            </div>
                            <h3 class="font-medium text-gray-900">Router Provisioning &amp; ZTP</h3>
                            <p class="mt-2 text-sm text-gray-600">Connect Mikrotik routers and walk through a guided zero-touch provisioning wizard &mdash; status checks and port configuration included.</p>
                        </div>
                        <div class="bg-white p-6 rounded-lg border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                            <div class="h-10 w-10 rounded-md bg-indigo-50 flex items-center justify-center text-indigo-600 mb-4">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            </div>
                            <h3 class="font-medium text-gray-900">Plan &amp; Package Management</h3>
                            <p class="mt-2 text-sm text-gray-600">Build hotspot data and time packages with custom pricing, duration, and speed limits, and roll them out across your routers.</p>
                        </div>
                        <div class="bg-white p-6 rounded-lg border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                            <div class="h-10 w-10 rounded-md bg-indigo-50 flex items-center justify-center text-indigo-600 mb-4">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                            </div>
                            <h3 class="font-medium text-gray-900">Hotspot User Management</h3>
                            <p class="mt-2 text-sm text-gray-600">Onboard subscribers, assign them to plans, and keep track of who's active across every router you manage.</p>
                        </div>
                        <div class="bg-white p-6 rounded-lg border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                            <div class="h-10 w-10 rounded-md bg-indigo-50 flex items-center justify-center text-indigo-600 mb-4">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg>
                            </div>
                            <h3 class="font-medium text-gray-900">Multi-Tenant Billing</h3>
                            <p class="mt-2 text-sm text-gray-600">Each ISP runs its own isolated workspace with its own routers, plans, customers, and transaction history.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Mikrotik hardware carousel --}}
            <section class="max-w-6xl mx-auto px-6 lg:px-8 py-20" data-reveal>
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span class="text-sm font-semibold text-indigo-600">Real Hardware Integration</span>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-gray-900">Built for real Mikrotik hardware</h2>
                    <p class="mt-4 text-gray-600">
                        RadiusPoint talks to your routers over the Mikrotik RouterOS API directly &mdash;
                        no manual configuration scripts to copy and paste by hand.
                    </p>
                </div>

                <div id="hw-carousel" class="grid lg:grid-cols-2 items-stretch bg-gray-50 rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                    <div class="relative h-72 lg:h-auto bg-white flex items-center justify-center p-10 overflow-hidden">
                        <img data-hw-img="0" src="{{ asset('images/hardware/rb4011.webp') }}" alt="Mikrotik RB4011iGS+5HacQ2HnD-IN router" class="absolute max-h-56 w-auto object-contain transition-all duration-700 ease-out" style="opacity: 1; transform: translateX(0);">
                        <img data-hw-img="1" src="{{ asset('images/hardware/ccr1009.webp') }}" alt="Mikrotik CCR1009-7G-1C-1S+ Cloud Core Router" class="absolute max-h-48 w-auto object-contain transition-all duration-700 ease-out" style="opacity: 0; transform: translateX(24px);">
                        <img data-hw-img="2" src="{{ asset('images/hardware/rb951.webp') }}" alt="Mikrotik RB951Ui-2HnD RouterBOARD" class="absolute max-h-48 w-auto object-contain transition-all duration-700 ease-out" style="opacity: 0; transform: translateX(24px);">
                    </div>

                    <div class="p-8 lg:p-12 flex flex-col justify-center">
                        <div data-hw-slide="0" class="hw-slide">
                            <span class="text-xs font-bold text-indigo-600 uppercase tracking-wide">Zero-Touch Provisioning</span>
                            <h3 class="mt-2 text-xl font-bold text-gray-900">RB4011iGS+5HacQ2HnD-IN</h3>
                            <p class="mt-3 text-sm text-gray-600 leading-relaxed">Paste one script into the terminal and RadiusPoint handles the rest &mdash; WireGuard tunnel, API access, and RADIUS integration configured automatically. No manual CLI work.</p>
                        </div>
                        <div data-hw-slide="1" class="hw-slide hidden">
                            <span class="text-xs font-bold text-indigo-600 uppercase tracking-wide">Built to Scale</span>
                            <h3 class="mt-2 text-xl font-bold text-gray-900">CCR1009-7G-1C-1S+</h3>
                            <p class="mt-3 text-sm text-gray-600 leading-relaxed">Cloud Core Routers handle high-throughput, multi-tenant deployments &mdash; built for ISPs running PPPoE at scale across hundreds of subscribers.</p>
                        </div>
                        <div data-hw-slide="2" class="hw-slide hidden">
                            <span class="text-xs font-bold text-indigo-600 uppercase tracking-wide">Hotspot-Ready</span>
                            <h3 class="mt-2 text-xl font-bold text-gray-900">RB951Ui-2HnD</h3>
                            <p class="mt-3 text-sm text-gray-600 leading-relaxed">A compact RouterBOARD built for hotspot deployments. Plug it in, run the provisioning wizard, and start selling packages in minutes.</p>
                        </div>

                        <div class="mt-8 flex items-center gap-2">
                            <button type="button" data-hw-dot="0" class="hw-dot w-2.5 h-2.5 rounded-full bg-indigo-600 transition-colors" aria-label="Show RB4011 slide"></button>
                            <button type="button" data-hw-dot="1" class="hw-dot w-2.5 h-2.5 rounded-full bg-gray-300 transition-colors" aria-label="Show CCR1009 slide"></button>
                            <button type="button" data-hw-dot="2" class="hw-dot w-2.5 h-2.5 rounded-full bg-gray-300 transition-colors" aria-label="Show RB951 slide"></button>
                        </div>
                    </div>
                </div>

                <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm text-gray-700">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        Guided zero-touch provisioning wizard
                    </div>
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        Live connection status checks per router
                    </div>
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        Port selection and configuration from the dashboard
                    </div>
                </div>
            </section>

            {{-- How it works --}}
            <section class="bg-gray-50 border-y border-gray-100" data-reveal>
                <div class="max-w-6xl mx-auto px-6 lg:px-8 py-20">
                    <h2 class="text-2xl font-semibold text-center text-gray-900">Joining is simple</h2>
                    <p class="mt-2 text-center text-gray-600">We review every new ISP account to keep the platform trustworthy.</p>

                    <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div class="text-center">
                            <div class="mx-auto h-9 w-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold">1</div>
                            <h3 class="mt-4 font-medium text-gray-900">Sign up</h3>
                            <p class="mt-1 text-sm text-gray-600">Tell us about your company and create your account.</p>
                        </div>
                        <div class="text-center">
                            <div class="mx-auto h-9 w-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold">2</div>
                            <h3 class="mt-4 font-medium text-gray-900">Verify your email</h3>
                            <p class="mt-1 text-sm text-gray-600">Confirm your email address using the link we send you.</p>
                        </div>
                        <div class="text-center">
                            <div class="mx-auto h-9 w-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold">3</div>
                            <h3 class="mt-4 font-medium text-gray-900">Get approved</h3>
                            <p class="mt-1 text-sm text-gray-600">Our team reviews your application and approves your account.</p>
                        </div>
                        <div class="text-center">
                            <div class="mx-auto h-9 w-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold">4</div>
                            <h3 class="mt-4 font-medium text-gray-900">Launch</h3>
                            <p class="mt-1 text-sm text-gray-600">Log in, provision your routers, and start selling hotspot plans.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- CTA --}}
            @guest
                <section class="bg-indigo-600" data-reveal>
                    <div class="max-w-6xl mx-auto px-6 lg:px-8 py-14 text-center">
                        <h2 class="text-2xl font-semibold text-white">Ready to get your ISP online?</h2>
                        <p class="mt-2 text-indigo-100">Create your account today &mdash; it only takes a couple of minutes.</p>
                        <a href="{{ route('register') }}" class="mt-6 inline-block px-6 py-3 bg-white text-indigo-600 font-medium rounded-md hover:bg-indigo-50 hover:scale-105 transition-transform">
                            Get Started
                        </a>
                    </div>
                </section>
            @endguest
        </main>

        <footer class="border-t border-gray-100">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-500">
                <div class="flex items-center gap-2">
                    <x-application-logo class="h-5 w-5 text-gray-400" />
                    <span>&copy; {{ date('Y') }} RadiusPoint. All rights reserved.</span>
                </div>
                <div class="flex items-center gap-6">
                    @guest
                        <a href="{{ route('login') }}" class="hover:text-gray-700">Log in</a>
                        <a href="{{ route('register') }}" class="hover:text-gray-700">Get Started</a>
                    @endguest
                </div>
            </div>
        </footer>

        <div class="fixed bottom-6 right-6 z-50 flex flex-col gap-3">
            <a href="https://wa.me/254790738021" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"
               class="w-14 h-14 rounded-full bg-green-500 hover:bg-green-600 flex items-center justify-center shadow-lg text-white transition-colors">
                <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.477 2 2 6.477 2 12c0 1.85.505 3.58 1.383 5.06L2 22l5.11-1.342A9.94 9.94 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2Zm0 18a7.94 7.94 0 0 1-4.06-1.11l-.29-.17-3.03.8.81-2.95-.19-.3A7.95 7.95 0 1 1 12 20Zm4.36-5.96c-.24-.12-1.42-.7-1.64-.78-.22-.08-.38-.12-.54.12-.16.24-.62.78-.76.94-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.92-1.18-.71-.63-1.19-1.41-1.33-1.65-.14-.24-.01-.37.11-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.19-.46-.39-.4-.54-.4h-.46c-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2s.86 2.32.98 2.48c.12.16 1.7 2.6 4.12 3.64.58.25 1.03.4 1.38.51.58.18 1.11.16 1.53.1.47-.07 1.42-.58 1.62-1.14.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28Z"/>
                </svg>
            </a>
            <a href="tel:+254790738021" aria-label="Call us"
               class="w-14 h-14 rounded-full bg-indigo-600 hover:bg-indigo-700 flex items-center justify-center shadow-lg text-white transition-colors">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                </svg>
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
                    slides.forEach(function (s, i) { s.classList.toggle('hidden', i !== index); });
                    imgs.forEach(function (img, i) {
                        img.style.opacity = i === index ? '1' : '0';
                        img.style.transform = i === index ? 'translateX(0)' : 'translateX(24px)';
                    });
                    dots.forEach(function (d, i) {
                        d.classList.toggle('bg-indigo-600', i === index);
                        d.classList.toggle('bg-gray-300', i !== index);
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
