<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'RadiusPoint') }}{{ $title ? ' — '.$title : '' }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        @vite(['resources/css/app.scss', 'resources/js/app.js'])

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">

        <script>
            // Applied synchronously, before the deferred module bundle even loads, so a
            // returning dark-mode/accent-themed user never sees a flash of the defaults while
            // the page boots. Dark mode falls back to the OS/browser preference the very first
            // time, before any choice is stored.
            (function () {
                var stored = localStorage.getItem('rp_dark_mode');
                var isDark = stored === null
                    ? window.matchMedia('(prefers-color-scheme: dark)').matches
                    : stored === '1';
                document.documentElement.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
                document.documentElement.setAttribute('data-accent-theme', localStorage.getItem('rp_accent_theme') || 'blue');
            })();
        </script>

        {{-- See App\Support\ThemePalette for the actual color values and how this reaches
             every page without rewriting individual Blade files. --}}
        <style>{!! \App\Support\ThemePalette::accentCss() !!}</style>
    </head>
    <body>
    {{-- === PAGE LOADER === A thin top progress bar for full-page navigations, since this app
         is a traditional MPA (no client-side routing) and page swaps can take a moment. Starts
         the instant a qualifying link/form triggers navigation, trickles while the browser
         fetches the next page, and only completes once that next page's own copy of this
         script reports in via `pageshow` — so it never lies about being "done" early. --}}
    <div id="rp-page-loader" class="position-fixed top-0 start-0 end-0" style="z-index:1100;height:3px;pointer-events:none">
        <div id="rp-page-loader-bar" style="height:100%;width:0;opacity:0;background:var(--tblr-primary);transition:width .3s ease-out,opacity .3s ease-out"></div>
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

    <div class="page">
        <aside class="navbar navbar-vertical navbar-expand-lg custom-scrollbar d-none d-lg-flex" id="rp-sidebar">
            <script>
                // Applies the persisted collapsed state before the deferred module bundle
                // loads, so a returning user doesn't see a flash of the wrong width first —
                // same reasoning as the dark-mode init script in <head>.
                if (localStorage.getItem('rp_sidebar_open') === '0') {
                    document.getElementById('rp-sidebar').classList.add('rp-collapsed');
                }
            </script>
            <div class="container-fluid">
                <h1 class="navbar-brand navbar-brand-autodark">
                    <a href="{{ Auth::user()->is_platform_admin ? route('platform-admin.dashboard') : route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                        <span class="avatar avatar-sm bg-primary-lt"><i class="ti ti-topology-star-3"></i></span>
                        <span class="navbar-brand-title text-truncate">
                            {{ Auth::user()->is_platform_admin ? 'Platform Admin' : Auth::user()->tenant->company_name }}
                        </span>
                    </a>
                </h1>

                <div class="collapse navbar-collapse" id="rp-sidebar-menu">
                    @include('partials._nav-links', ['navId' => 'desktop'])
                </div>
            </div>
        </aside>

        {{-- === MOBILE NAV DRAWER === Below lg, the desktop rail above is hidden entirely (a
             fixed-position aside with no visible content still occupies space — see the
             earlier "empty navbar" bug) and this slides in from the left instead, opened by
             the header's hamburger (rp-shell.js). Same offcanvas system as every other panel
             in the app (Add Customer, filters, …), so it gets the same width/backdrop/full-
             screen-on-phone treatment for free. --}}
        <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="rp-mobile-nav" data-bs-scroll="false" aria-hidden="true">
            <div class="offcanvas-header border-bottom">
                <h1 class="navbar-brand mb-0">
                    <a href="{{ Auth::user()->is_platform_admin ? route('platform-admin.dashboard') : route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none" data-bs-dismiss="offcanvas">
                        <span class="avatar avatar-sm bg-primary-lt"><i class="ti ti-topology-star-3"></i></span>
                        <span class="fw-bold text-truncate">
                            {{ Auth::user()->is_platform_admin ? 'Platform Admin' : Auth::user()->tenant->company_name }}
                        </span>
                    </a>
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0 custom-scrollbar">
                @include('partials._nav-links', ['navId' => 'mobile'])
            </div>
        </div>

        <div class="page-wrapper">
            <header class="navbar navbar-expand-md navbar-light d-print-none rp-header-float">
                <div class="container-fluid">
                    {{-- One control for both breakpoints — rp-shell.js checks the viewport at
                         click time: below lg it opens #rp-mobile-nav, at lg+ it does the
                         desktop rail-collapse instead. --}}
                    <button type="button" class="btn btn-icon" id="rp-sidebar-toggle" title="Toggle sidebar">
                        <i class="ti ti-menu-2 icon"></i>
                    </button>

                    @unless(Auth::user()->is_platform_admin)
                        {{-- No visible search icon (matches the reference header) — Ctrl+K opens the
                             same modal; see resources/js/rp-search.js. --}}
                        <button type="button" class="visually-hidden" id="rp-search-trigger">Search</button>
                    @endunless

                    <div class="navbar-nav flex-row order-md-last align-items-center gap-2">
                        <button type="button" class="nav-link px-2 rp-theme-toggle" title="Toggle dark mode">
                            <i class="ti ti-moon icon"></i>
                        </button>

                        <div class="nav-item dropdown d-none d-md-block"
                             id="rp-notif-root"
                             data-recent-url="{{ route('notifications.recent') }}"
                             data-mark-all-url="{{ route('notifications.mark-all-read') }}">
                            <a href="#" class="nav-link px-2 position-relative" data-bs-toggle="dropdown" aria-label="Notifications">
                                <i class="ti ti-bell icon"></i>
                                <span id="rp-notif-badge" class="badge bg-red position-absolute top-0 end-0" style="display:none;width:.5rem;height:.5rem;padding:0;border-radius:50%"></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-card">
                                <div class="card">
                                    <div class="card-header d-flex align-items-center justify-content-between">
                                        <h3 class="card-title mb-0">Notifications</h3>
                                        <a href="{{ route('settings.notifications.edit') }}" class="text-muted small">Preferences</a>
                                    </div>
                                    <div class="list-group list-group-flush list-group-hoverable" id="rp-notif-list" style="max-height:24rem;overflow-y:auto"></div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a href="{{ route('notifications.index') }}" class="small">View All</a>
                                        <button type="button" id="rp-notif-mark-all" class="btn btn-link btn-sm p-0" style="display:none">Mark all read</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link d-flex lh-1 p-0 px-2 gap-2 align-items-center" data-bs-toggle="dropdown" aria-label="Open user menu">
                                <span class="position-relative flex-shrink-0">
                                    <span class="avatar avatar-sm rp-avatar-gradient fw-bold">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
                                    <span class="position-absolute rounded-circle bg-green rp-avatar-status" aria-hidden="true"></span>
                                </span>
                                <div class="d-none d-xl-flex align-items-center gap-1 ps-2">
                                    <div class="fw-bold">{{ Auth::user()->name }}</div>
                                    <i class="ti ti-chevron-down text-muted" style="font-size:1rem"></i>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                @if(Auth::user()->is_platform_admin)
                                    <a href="{{ route('profile.edit') }}" class="dropdown-item"><i class="ti ti-user icon"></i> Profile &amp; Appearance</a>
                                    {{-- Configures the *platform's own* M-Pesa till/paybill (tenant_id=config('billing.platform_tenant_id')) —
                                         this is what receives tenant commission payments, see TenantBillingController::pay(). --}}
                                    <a href="{{ route('mpesa-settings.edit') }}" class="dropdown-item {{ request()->routeIs('mpesa-settings.*') ? 'active' : '' }}"><i class="ti ti-credit-card icon"></i> Platform M-Pesa Settings</a>
                                @else
                                    <a href="{{ route('account.index') }}" class="dropdown-item {{ request()->routeIs('account.*') ? 'active' : '' }}"><i class="ti ti-user-circle icon"></i> Account Settings</a>
                                @endif
                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="ti ti-logout icon"></i> Log Out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Toast stack, top-right of the viewport. Seeded from whatever flash message
                 this request carries; see resources/js/rp-toasts.js for window.rpToast(). --}}
            <div id="rp-toast-stack" class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100"></div>
            @if (session('success') || session('error'))
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        @if (session('success')) window.rpToast('success', @json(session('success'))); @endif
                        @if (session('error')) window.rpToast('error', @json(session('error'))); @endif
                    });
                </script>
            @endif

            <div class="page-body">
                <div class="container-xl">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    @include('partials._user-detail-panel')
    @include('partials._confirm-dialog')
    @unless(Auth::user()->is_platform_admin)
        @include('partials._search-modal')
    @endunless

    {{ $scripts ?? '' }}
    </body>
</html>
