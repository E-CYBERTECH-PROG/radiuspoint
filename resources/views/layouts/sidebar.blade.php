<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'RadiusPoint') }}{{ $title ? ' — '.$title : '' }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        {{-- Precompiled Tailwind build (see tailwind.config.js) instead of the CDN "Play" build
             — that JIT-compiled every utility class from scratch on every single page load,
             which was the actual cause of the ~1-2s felt navigation lag. This is a plain
             built CSS file (not Alpine/JS), so it carries none of the subdirectory/manifest
             risk that broke a prior attempt to move the JS bundle onto Vite too — Alpine stays
             on its existing CDN script, untouched. --}}
        @vite(['resources/css/app.css'])

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

        <script>
            // Applied synchronously, before Alpine even loads (it's deferred), so a returning
            // dark-mode/themed user never sees a flash of the defaults while the page boots.
            // Dark mode falls back to the OS/browser preference the very first time, before any
            // choice is stored; the three color roles (text/card/button) each independently
            // default to "blue" (the app's original, untouched color — see ThemePalette). These
            // only affect page *content* now — the sidebar/header below have their own fixed
            // indigo look, not the user's chosen accent.
            (function () {
                var stored = localStorage.getItem('rp_dark_mode');
                var isDark = stored === null
                    ? window.matchMedia('(prefers-color-scheme: dark)').matches
                    : stored === '1';
                document.documentElement.classList.toggle('dark', isDark);

                ['text', 'card', 'button'].forEach(function (role) {
                    var color = localStorage.getItem('rp_theme_' + role) || 'blue';
                    document.documentElement.setAttribute('data-' + role + '-theme', color);
                });
            })();
        </script>

        {{--
            Theme accent overrides — see App\Support\ThemePalette for the actual color values
            and how this reaches every page without rewriting individual Blade files. Only
            applies to page content (buttons, links, cards); the sidebar/header below use
            fixed indigo classes untouched by this.
        --}}
        <style>
            {!! \App\Support\ThemePalette::textCss() !!}
            {!! \App\Support\ThemePalette::buttonCss() !!}
            {!! \App\Support\ThemePalette::cardCss() !!}
        </style>

        {{-- Must live in <head> — every modal on every page uses x-cloak to stay hidden until
             Alpine mounts, but Alpine's own script is deferred (loads after the page parses).
             A <style> tag defined later in the body doesn't apply in time to stop the browser
             from briefly painting those modals open first, which is exactly the flash this
             was causing on every navigation. --}}
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body>
    {{-- === PAGE LOADER === A thin top progress bar for full-page navigations, since this app
         is a traditional MPA (no client-side routing) and page swaps can take a moment. Starts
         the instant a qualifying link/form triggers navigation, trickles while the browser
         fetches the next page, and only completes once that next page's own copy of this
         script reports in via `pageshow` — so it never lies about being "done" early. --}}
    <div id="rp-page-loader" class="fixed top-0 left-0 right-0 z-[200] h-[3px] pointer-events-none">
        <div id="rp-page-loader-bar" class="h-full bg-indigo-600 shadow-[0_0_8px_rgba(99,102,241,0.65)] transition-[width,opacity] duration-300 ease-out" style="width:0%;opacity:0;"></div>
    </div>
    <script>
        (function () {
            var bar = document.getElementById('rp-page-loader-bar');
            var visible = false;
            var width = 0;
            var trickleTimer = null;

            function show() {
                if (visible) return;
                visible = true;
                width = 20;
                bar.style.opacity = '1';
                bar.style.width = width + '%';
                // Asymptotically creeps toward 90% — it never claims to be finished until the
                // real page actually finishes loading (see the pageshow listener below).
                trickleTimer = setInterval(function () {
                    width += (90 - width) * 0.1;
                    bar.style.width = width + '%';
                }, 200);
            }

            function reset() {
                clearInterval(trickleTimer);
                visible = false;
                width = 0;
                bar.style.width = '0%';
                bar.style.opacity = '0';
            }

            // Exposed so _confirm-dialog.blade.php can start the bar at the moment a
            // confirmed form actually submits — that path bypasses the native 'submit' event
            // (by design, to avoid re-triggering rpConfirm), so it can't be caught below.
            window.rpShowPageLoader = show;

            document.addEventListener('click', function (e) {
                var a = e.target.closest('a');
                if (!a || !a.getAttribute('href')) return;
                if (a.target && a.target !== '_self') return;
                if (a.hasAttribute('download')) return;
                if (a.getAttribute('href').charAt(0) === '#') return;
                if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

                var url;
                try { url = new URL(a.href, window.location.href); } catch (err) { return; }
                if (url.origin !== window.location.origin) return;
                if (url.href === window.location.href) return;

                show();
            });

            document.addEventListener('submit', function (e) {
                if (e.defaultPrevented) return;
                show();
            });

            // Fires on every fresh document load AND on back/forward-cache restores — either
            // way, this is a brand-new (or freshly-shown) page, so any bar left over from the
            // navigation that got us here is done.
            window.addEventListener('pageshow', reset);
        })();
    </script>

    <div x-data="{ sidebarOpen: (localStorage.getItem('rp_sidebar_open') ?? '1') === '1', sidebarPeek: false, mobileMenu: false, darkMode: document.documentElement.classList.contains('dark') }"
         class="flex h-screen bg-gray-100 dark:bg-gray-950 transition-colors duration-300 font-sans text-gray-800 dark:text-gray-200 overflow-hidden">

        <div x-show="mobileMenu" @click="mobileMenu = false" class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm lg:hidden"></div>

        {{-- Desktop layout spacer — reserves the collapsed/expanded width in normal flex flow so
             <main> doesn't reflow when the fixed-position <aside> below temporarily widens into
             a "peek" overlay on hover (matching one-isp's collapsed-sidebar hover behavior). --}}
        <div class="hidden lg:block shrink-0 h-screen transition-all duration-300 ease-in-out"
             x-bind:class="{ 'w-[260px]': sidebarOpen, 'w-20': !sidebarOpen }">
            <script>
                document.currentScript.parentElement.classList.add(
                    localStorage.getItem('rp_sidebar_open') === '0' ? 'w-20' : 'w-[260px]'
                );
            </script>
        </div>

        {{-- Object-form :class (not string concatenation) is required here — Alpine's string-form
             class binding only ever ADDS the computed classes, it never removes a class from a
             previous evaluation (or one set by the pre-mount script below) that isn't present in
             the new string. That left both `w-20` and `w-[260px]` stuck on the element at once
             after toggling, and Tailwind's cascade order happened to make the arbitrary-value
             `w-[260px]` win regardless of which one was "supposed" to be active — the exact bug
             behind the dead gap between the collapsed icon rail and the page content. Object-form
             `:class="{ 'x': cond }"` correctly diffs and removes classes whose condition flips to
             false. --}}
        <aside class="shrink-0 fixed top-0 left-0 z-50 h-screen bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 transition-all duration-300 ease-in-out flex flex-col"
               @mouseenter="if (!sidebarOpen) sidebarPeek = true" @mouseleave="sidebarPeek = false"
               x-bind:class="{
                   'w-[260px]': sidebarOpen || sidebarPeek,
                   'w-20': !sidebarOpen && !sidebarPeek,
                   'translate-x-0': mobileMenu,
                   '-translate-x-full lg:translate-x-0': !mobileMenu,
                   'shadow-xl': mobileMenu || (sidebarPeek && !sidebarOpen),
                   'shadow-none': !mobileMenu && !(sidebarPeek && !sidebarOpen)
               }">
            <script>
                // Applies the persisted width before Alpine mounts (Alpine's script is deferred),
                // so a returning user doesn't see a flash of the wrong width first — same
                // reasoning as the dark-mode init script above. The reactive :class binding above
                // is the only thing that controls width once Alpine takes over; this just covers
                // the gap before that happens.
                document.currentScript.parentElement.classList.add(
                    localStorage.getItem('rp_sidebar_open') === '0' ? 'w-20' : 'w-[260px]'
                );
            </script>

            <div class="flex items-center justify-between h-16 px-4">
                <a href="{{ Auth::user()->is_platform_admin ? route('platform-admin.dashboard') : route('dashboard') }}" class="flex items-center gap-2.5 overflow-hidden min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shrink-0 shadow-sm">
                        <i class="bx bxs-network-chart text-white text-base"></i>
                    </div>
                    <span class="font-extrabold text-base text-gray-900 dark:text-white whitespace-nowrap truncate" x-show="sidebarOpen || sidebarPeek">
                        {{ Auth::user()->is_platform_admin ? 'Platform Admin' : Auth::user()->tenant->company_name }}
                    </span>
                </a>
                @unless(Auth::user()->is_platform_admin)
                    <a href="{{ route('search') }}" x-show="sidebarOpen || sidebarPeek" title="Search" class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                        <i class="bx bx-search text-lg"></i>
                    </a>
                @endunless
            </div>

            <nav class="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto custom-scrollbar">
                @if(Auth::user()->is_platform_admin)
                    <a href="{{ route('platform-admin.dashboard') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('platform-admin.dashboard') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <i class="bx bxs-dashboard text-lg shrink-0"></i>
                        <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Dashboard</span>
                    </a>

                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 mt-3" x-show="sidebarOpen || sidebarPeek">Platform</p>
                    <div x-data="{ open: {{ request()->routeIs('platform-admin.*') && ! request()->routeIs('platform-admin.dashboard') ? 'true' : 'false' }} }">
                        <button @click="open = !open" class="w-full flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                            <i class="bx bx-shield-quarter text-lg shrink-0"></i>
                            <span class="ml-3 font-bold flex-1 text-left whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Platform</span>
                            @php
                                $pendingCount = \App\Models\Tenant::where('status', 'pending')->count();
                            @endphp
                            @if($pendingCount > 0)
                                <span class="bg-rose-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full mr-2" x-show="sidebarOpen || sidebarPeek">{{ $pendingCount }}</span>
                            @endif
                            <i class="bx bx-chevron-down transition-transform" :class="open ? 'rotate-180' : ''" x-show="sidebarOpen || sidebarPeek"></i>
                        </button>
                        <div x-show="open && (sidebarOpen || sidebarPeek)" class="ml-9 mt-1 space-y-0.5">
                            <a href="{{ route('platform-admin.tenants.index') }}" class="block py-0.5 text-sm {{ request()->routeIs('platform-admin.tenants.index') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">Tenants</a>
                            <a href="{{ route('platform-admin.tenants.import-form') }}" class="block py-0.5 text-sm {{ request()->routeIs('platform-admin.tenants.import-form') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">Import Tenants</a>
                            <a href="{{ route('platform-admin.invoices.index') }}" class="block py-0.5 text-sm {{ request()->routeIs('platform-admin.invoices.index') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">Commission Invoices</a>
                            <a href="{{ route('platform-admin.activity-log.index') }}" class="block py-0.5 text-sm {{ request()->routeIs('platform-admin.activity-log.index') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">Activity Log</a>
                        </div>
                    </div>
                @else
                    <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <i class="bx bxs-dashboard text-lg shrink-0"></i>
                        <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Dashboards</span>
                    </a>

                    {{-- === MANAGEMENT === --}}
                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 pt-6" x-show="sidebarOpen || sidebarPeek">Management</p>

                    <a href="{{ route('routers.index') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('routers.*') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <i class="bx bx-globe text-lg shrink-0"></i>
                        <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">NAS</span>
                    </a>

                    <a href="{{ route('vouchers.index') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('vouchers.*') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <i class="bx bx-barcode-reader text-lg shrink-0"></i>
                        <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Vouchers</span>
                    </a>

                    <a href="{{ route('plans.index') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('plans.*') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <i class="bx bx-box text-lg shrink-0"></i>
                        <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Packages</span>
                    </a>

                    {{-- === CRM === --}}
                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 pt-6" x-show="sidebarOpen || sidebarPeek">CRM</p>

                    <a href="{{ route('customers.index') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs(['customers.*', 'pppoe-users.*', 'hotspot-users.*']) ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <i class="bx bx-group text-lg shrink-0"></i>
                        <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Customers</span>
                    </a>

                    <a href="{{ route('leads.index') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('leads.*') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <i class="bx bx-user-plus text-lg shrink-0"></i>
                        <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Leads</span>
                    </a>

                    <a href="{{ route('tickets.index') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('tickets.*') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <i class="bx bx-support text-lg shrink-0"></i>
                        <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Tickets</span>
                    </a>

                    {{-- === REVENUE === --}}
                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 pt-6" x-show="sidebarOpen || sidebarPeek">Revenue</p>

                    <a href="{{ route('reports.receipts') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('reports.receipts*') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <i class="bx bx-wallet text-lg shrink-0"></i>
                        <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Payments</span>
                    </a>

                    <a href="{{ route('transactions.index') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('transactions.*') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <i class="bx bx-receipt text-lg shrink-0"></i>
                        <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Transactions</span>
                    </a>

                    @if(Auth::user()->role !== 'Sales Agent')
                        <a href="{{ route('billing.edit') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('billing.*') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                            <i class="bx bx-file text-lg shrink-0"></i>
                            <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Invoices</span>
                        </a>
                    @endif

                    <a href="{{ route('expenses.index') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('expenses.*') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <i class="bx bx-money-withdraw text-lg shrink-0"></i>
                        <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Expenses</span>
                    </a>

                    <div x-data="{ open: {{ request()->routeIs('reports.*') && ! request()->routeIs('reports.receipts*') ? 'true' : 'false' }} }">
                        <button @click="open = !open" class="w-full flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('reports.*') && ! request()->routeIs('reports.receipts*') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                            <i class="bx bxs-report text-lg shrink-0"></i>
                            <span class="ml-3 flex-1 text-left whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Reports</span>
                            <i class="bx bx-chevron-down transition-transform" :class="open ? 'rotate-180' : ''" x-show="sidebarOpen || sidebarPeek"></i>
                        </button>
                        <div x-show="open && (sidebarOpen || sidebarPeek)" class="ml-9 mt-1 space-y-0.5">
                            <a href="{{ route('reports.pppoe-balances') }}" class="block py-0.5 text-sm {{ request()->routeIs('reports.pppoe-balances') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">Fixed User Balances</a>
                            <a href="{{ route('reports.fixed-sales') }}" class="block py-0.5 text-sm {{ request()->routeIs('reports.fixed-sales') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">Fixed Service Sales</a>
                            <a href="{{ route('reports.hotspot-sales') }}" class="block py-0.5 text-sm {{ request()->routeIs('reports.hotspot-sales') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">Hotspot Service Sales</a>
                            <a href="{{ route('reports.access-log') }}" class="block py-0.5 text-sm {{ request()->routeIs('reports.access-log') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">Access Requests</a>
                            <a href="{{ route('reports.expired-users') }}" class="block py-0.5 text-sm {{ request()->routeIs('reports.expired-users') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">Expired Users</a>
                            <a href="{{ route('reports.analytics') }}" class="block py-0.5 text-sm {{ request()->routeIs('reports.analytics') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400' }}">Analytics</a>
                        </div>
                    </div>

                    {{-- === COMMS === --}}
                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 pt-6" x-show="sidebarOpen || sidebarPeek">Comms</p>

                    <a href="{{ route('sms.index') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('sms.*') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <i class="bx bx-message-square-dots text-lg shrink-0"></i>
                        <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">SMS</span>
                    </a>

                    @if(Auth::user()->role !== 'Sales Agent')
                        <a href="{{ route('captive-announcements.index') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('captive-announcements.*') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                            <i class="bx bx-bell text-lg shrink-0"></i>
                            <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Announcements</span>
                        </a>
                    @endif

                    {{-- === SETTINGS === --}}
                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 pt-6" x-show="sidebarOpen || sidebarPeek">Settings</p>

                    @if(Auth::user()->role === 'SuperAdmin')
                        <a href="{{ route('team.index') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('team.*') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                            <i class="bx bx-lock-alt text-lg shrink-0"></i>
                            <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Access Control</span>
                        </a>
                    @endif

                    <a href="{{ route('account.index') }}" class="flex items-center px-3 py-1 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('account.*') ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <i class="bx bx-user-circle text-lg shrink-0"></i>
                        <span class="ml-3 whitespace-nowrap" x-show="sidebarOpen || sidebarPeek">Account</span>
                    </a>
                @endif
            </nav>
        </aside>

        <main class="flex-1 flex flex-col h-screen overflow-hidden bg-gray-100 dark:bg-gray-950">

            <header class="mx-4 mt-4 lg:mx-8 lg:mt-6 h-16 shrink-0 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm flex items-center justify-between px-4 lg:px-6 z-30">
                <div class="flex items-center gap-4">
                    <button @click="mobileMenu = true" class="lg:hidden text-gray-500 hover:text-indigo-600">
                        <i class="bx bx-menu text-2xl"></i>
                    </button>
                    <button @click="sidebarOpen = !sidebarOpen; localStorage.setItem('rp_sidebar_open', sidebarOpen ? '1' : '0')" class="hidden lg:block text-gray-500 hover:text-indigo-600 transition-colors">
                        <i class="bx text-2xl" :class="sidebarOpen ? 'bx-menu-alt-left' : 'bx-menu'"></i>
                    </button>
                </div>

                <div class="flex items-center gap-4 lg:gap-5">
                    <button @click="
                        darkMode = !darkMode;
                        document.documentElement.classList.toggle('dark', darkMode);
                        localStorage.setItem('rp_dark_mode', darkMode ? '1' : '0');
                    " title="Toggle dark mode" class="text-gray-500 hover:text-amber-500 dark:hover:text-amber-400 transition-colors focus:outline-none">
                        <i class="bx text-2xl" :class="darkMode ? 'bx-sun' : 'bx-moon'"></i>
                    </button>

                    @php
                        $rpNotifItems = Auth::user()->notifications()->latest()->limit(10)->get()->map(fn ($n) => [
                            'id' => $n->id,
                            'message' => $n->data['message'] ?? 'Notification',
                            'url' => $n->data['url'] ?? null,
                            'read' => (bool) $n->read_at,
                            'created_at_human' => $n->created_at->diffForHumans(),
                        ]);
                    @endphp
                    <div x-data="{
                            notifOpen: false,
                            unread: {{ Auth::user()->unreadNotifications->count() }},
                            notifications: {{ Illuminate\Support\Js::from($rpNotifItems) }},
                            openNotification(n) {
                                this.notifOpen = false;
                                if (!n.read) {
                                    fetch(`{{ url('/notifications') }}/${n.id}/mark-read`, {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                    });
                                }
                                if (n.url) window.location.href = n.url;
                            },
                            async markAllRead() {
                                await fetch('{{ route('notifications.mark-all-read') }}', {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                });
                                this.unread = 0;
                                this.notifications = this.notifications.map(n => ({ ...n, read: true }));
                            },
                         }" class="relative">
                        <button @click="notifOpen = !notifOpen" class="relative text-gray-500 hover:text-indigo-600 transition-colors">
                            <i class="bx bx-bell text-2xl"></i>
                            <span x-show="unread > 0" x-cloak class="absolute top-0 right-0 w-2 h-2 bg-rose-500 rounded-full border border-white dark:border-gray-950"></span>
                        </button>
                        <div x-show="notifOpen" x-cloak x-transition @click.outside="notifOpen = false" class="fixed sm:absolute left-3 right-3 sm:left-auto sm:right-0 top-[4.5rem] sm:top-auto mt-0 sm:mt-2 w-auto sm:w-80 sm:max-w-80 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-lg py-1 z-50 max-h-[calc(100vh-6rem)] sm:max-h-96 overflow-y-auto">
                            <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100 dark:border-gray-800 sticky top-0 bg-white dark:bg-gray-900">
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Notifications</p>
                                <a href="{{ route('settings.notifications.edit') }}" class="text-[10px] font-bold text-gray-400 hover:text-indigo-600 uppercase tracking-wide">Preferences</a>
                            </div>
                            <template x-if="notifications.length === 0">
                                <p class="px-4 py-6 text-center text-xs text-gray-400">No notifications yet.</p>
                            </template>
                            <template x-for="n in notifications" :key="n.id">
                                <button type="button" @click="openNotification(n)" class="w-full text-left px-4 py-3 text-sm border-b border-gray-50 dark:border-gray-900 last:border-0 flex items-start gap-2 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors" :class="!n.read ? 'bg-indigo-50 dark:bg-indigo-900/10' : ''">
                                    <span class="w-1.5 h-1.5 rounded-full mt-1.5 shrink-0" :class="!n.read ? 'bg-indigo-500' : ''"></span>
                                    <div>
                                        <p class="text-gray-700 dark:text-gray-300" x-text="n.message"></p>
                                        <p class="text-[10px] text-gray-400 mt-1" x-text="n.created_at_human"></p>
                                    </div>
                                </button>
                            </template>
                            <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-2">
                                <a href="{{ route('notifications.index') }}" class="text-[10px] font-bold text-gray-500 hover:text-indigo-600 uppercase tracking-wide">View All</a>
                                <button type="button" x-show="unread > 0" @click="markAllRead()" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-700 uppercase tracking-wide">Mark all read</button>
                            </div>
                        </div>
                    </div>

                    <div class="h-8 w-px bg-gray-200 dark:bg-gray-700 hidden sm:block"></div>
                    <div x-data="{ profileOpen: false }" class="relative">
                        <button @click="profileOpen = !profileOpen" @click.outside="profileOpen = false" class="flex items-center gap-2.5 cursor-pointer">
                            <div class="relative">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-white flex items-center justify-center font-bold text-sm shadow-md">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                                <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-white dark:border-gray-950"></span>
                            </div>
                            <div class="hidden sm:block text-left">
                                <p class="text-sm font-bold text-gray-900 dark:text-white leading-tight">{{ Auth::user()->name }}</p>
                            </div>
                            <i class="bx bx-chevron-down text-gray-400 hidden sm:block"></i>
                        </button>
                        <div x-show="profileOpen" x-cloak x-transition @click.outside="profileOpen = false" class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-lg py-1 z-50">
                            @if(Auth::user()->is_platform_admin)
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"><i class="bx bx-user text-base text-gray-400"></i> Profile &amp; Appearance</a>
                                {{-- Configures the *platform's own* M-Pesa till/paybill (tenant_id=config('billing.platform_tenant_id')) —
                                     this is what receives tenant commission payments, see TenantBillingController::pay(). --}}
                                <a href="{{ route('mpesa-settings.edit') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 {{ request()->routeIs('mpesa-settings.*') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : '' }}"><i class="bx bx-credit-card text-base text-gray-400"></i> Platform M-Pesa Settings</a>
                            @else
                                <a href="{{ route('account.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 {{ request()->routeIs('account.*') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : '' }}"><i class="bx bx-user-circle text-base text-gray-400"></i> Account Settings</a>
                            @endif
                            <div class="border-t border-gray-100 dark:border-gray-800 my-1"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"><i class="bx bx-log-out text-base text-gray-400"></i> Log Out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{--
                Sliding toast stack, top-right of the viewport — replaces the old inline
                banner that pushed page content down. `toasts` seeds from whatever flash
                message this request carries; new ones (e.g. from a future SSE/polling
                source) could push into the same array without touching this markup.
            --}}
            <div
                x-data="{
                    toasts: [
                        @if (session('success')) { id: Date.now(), type: 'success', message: {{ Illuminate\Support\Js::from(session('success')) }} }, @endif
                        @if (session('error')) { id: Date.now() + 1, type: 'error', message: {{ Illuminate\Support\Js::from(session('error')) }} }, @endif
                    ],
                    dismiss(id) { this.toasts = this.toasts.filter(t => t.id !== id); },
                }"
                x-init="toasts.forEach(t => setTimeout(() => dismiss(t.id), 5000))"
                class="fixed top-4 right-4 z-[100] w-full max-w-sm px-4 sm:px-0 space-y-2 pointer-events-none"
            >
                <template x-for="toast in toasts" :key="toast.id">
                    <div
                        x-show="true"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-x-8"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 translate-x-8"
                        class="pointer-events-auto flex items-center justify-between gap-3 rounded-lg shadow-lg border px-4 py-3 text-sm font-bold"
                        :class="toast.type === 'success'
                            ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-900/50 text-emerald-700 dark:text-emerald-400'
                            : 'bg-rose-50 dark:bg-rose-900/20 border-rose-200 dark:border-rose-900/50 text-rose-700 dark:text-rose-400'"
                    >
                        <span class="flex items-center gap-2">
                            <i class="bx text-lg" :class="toast.type === 'success' ? 'bxs-check-circle' : 'bxs-error-circle'"></i>
                            <span x-text="toast.message"></span>
                        </span>
                        <button @click="dismiss(toast.id)" class="shrink-0 opacity-70 hover:opacity-100"><i class="bx bx-x text-lg"></i></button>
                    </div>
                </template>
            </div>

            <div class="flex-1 overflow-y-auto p-4 lg:p-8">
                {{ $slot }}
            </div>
        </main>
    </div>

    @include('partials._user-detail-panel')
    @include('partials._confirm-dialog')

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{ $scripts ?? '' }}

    <style>
        {{-- Auto-hiding scrollbar: invisible at rest, fades in only on hover/scroll. --}}
        .custom-scrollbar { scrollbar-width: none; }
        .custom-scrollbar:hover { scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
        .dark .custom-scrollbar:hover { scrollbar-color: #374151 transparent; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: transparent; border-radius: 10px; transition: background-color 0.2s ease; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #cbd5e1; }
        .dark .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #374151; }
    </style>
    </body>
</html>
