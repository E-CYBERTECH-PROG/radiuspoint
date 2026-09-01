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
        <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark" id="rp-sidebar">
            <script>
                // Applies the persisted collapsed state before the deferred module bundle
                // loads, so a returning user doesn't see a flash of the wrong width first —
                // same reasoning as the dark-mode init script in <head>.
                if (localStorage.getItem('rp_sidebar_open') === '0') {
                    document.getElementById('rp-sidebar').classList.add('rp-collapsed');
                }
            </script>
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#rp-sidebar-menu" aria-controls="rp-sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <h1 class="navbar-brand navbar-brand-autodark">
                    <a href="{{ Auth::user()->is_platform_admin ? route('platform-admin.dashboard') : route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                        <span class="avatar avatar-sm bg-primary-lt"><i class="ti ti-topology-star-3"></i></span>
                        <span class="navbar-brand-title text-truncate">
                            {{ Auth::user()->is_platform_admin ? 'Platform Admin' : Auth::user()->tenant->company_name }}
                        </span>
                    </a>
                </h1>

                <div class="navbar-nav flex-row d-lg-none">
                    <button type="button" class="nav-link px-0 rp-theme-toggle">
                        <i class="ti ti-moon icon"></i>
                    </button>
                </div>

                <div class="collapse navbar-collapse" id="rp-sidebar-menu">
                    <ul class="navbar-nav pt-lg-3">
                        @if(Auth::user()->is_platform_admin)
                            <li class="nav-item {{ request()->routeIs('platform-admin.dashboard') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('platform-admin.dashboard') }}">
                                    <span class="nav-link-icon"><i class="ti ti-layout-dashboard"></i></span>
                                    <span class="nav-link-title">Dashboard</span>
                                </a>
                            </li>

                            @php $pendingCount = \App\Models\Tenant::where('status', 'pending')->count(); @endphp
                            <li class="nav-item {{ request()->routeIs('platform-admin.*') && ! request()->routeIs('platform-admin.dashboard') ? 'active' : '' }}">
                                <a class="nav-link" href="#rp-nav-platform" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('platform-admin.*') && ! request()->routeIs('platform-admin.dashboard') ? 'true' : 'false' }}" aria-controls="rp-nav-platform">
                                    <span class="nav-link-icon"><i class="ti ti-shield-lock"></i></span>
                                    <span class="nav-link-title">Platform</span>
                                    @if($pendingCount > 0)
                                        <span class="badge bg-red text-white ms-auto rp-nav-badge">{{ $pendingCount }}</span>
                                    @endif
                                </a>
                                <div class="collapse {{ request()->routeIs('platform-admin.*') && ! request()->routeIs('platform-admin.dashboard') ? 'show' : '' }}" id="rp-nav-platform">
                                    <ul class="nav nav-pills flex-column ms-4">
                                        <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('platform-admin.tenants.index') ? 'active' : '' }}" href="{{ route('platform-admin.tenants.index') }}">Tenants</a></li>
                                        <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('platform-admin.tenants.import-form') ? 'active' : '' }}" href="{{ route('platform-admin.tenants.import-form') }}">Import Tenants</a></li>
                                        <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('platform-admin.invoices.index') ? 'active' : '' }}" href="{{ route('platform-admin.invoices.index') }}">Commission Invoices</a></li>
                                        <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('platform-admin.activity-log.index') ? 'active' : '' }}" href="{{ route('platform-admin.activity-log.index') }}">Activity Log</a></li>
                                    </ul>
                                </div>
                            </li>
                        @else
                            <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('dashboard') }}">
                                    <span class="nav-link-icon"><i class="ti ti-layout-dashboard"></i></span>
                                    <span class="nav-link-title">Dashboard</span>
                                </a>
                            </li>

                            <li class="nav-item mt-3"><span class="nav-link-title px-2 text-uppercase small text-muted fw-bold">Management</span></li>

                            <li class="nav-item {{ request()->routeIs('routers.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('routers.index') }}">
                                    <span class="nav-link-icon"><i class="ti ti-world"></i></span>
                                    <span class="nav-link-title">NAS</span>
                                </a>
                            </li>

                            <li class="nav-item {{ request()->routeIs('vouchers.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('vouchers.index') }}">
                                    <span class="nav-link-icon"><i class="ti ti-ticket"></i></span>
                                    <span class="nav-link-title">Vouchers</span>
                                </a>
                            </li>

                            <li class="nav-item {{ request()->routeIs('plans.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('plans.index') }}">
                                    <span class="nav-link-icon"><i class="ti ti-box"></i></span>
                                    <span class="nav-link-title">Packages</span>
                                </a>
                            </li>

                            <li class="nav-item mt-3"><span class="nav-link-title px-2 text-uppercase small text-muted fw-bold">CRM</span></li>

                            <li class="nav-item {{ request()->routeIs(['customers.*', 'pppoe-users.*', 'hotspot-users.*']) ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('customers.index') }}">
                                    <span class="nav-link-icon"><i class="ti ti-users"></i></span>
                                    <span class="nav-link-title">Customers</span>
                                </a>
                            </li>

                            <li class="nav-item {{ request()->routeIs('leads.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('leads.index') }}">
                                    <span class="nav-link-icon"><i class="ti ti-user-plus"></i></span>
                                    <span class="nav-link-title">Leads</span>
                                </a>
                            </li>

                            <li class="nav-item {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('tickets.index') }}">
                                    <span class="nav-link-icon"><i class="ti ti-headset"></i></span>
                                    <span class="nav-link-title">Tickets</span>
                                </a>
                            </li>

                            <li class="nav-item mt-3"><span class="nav-link-title px-2 text-uppercase small text-muted fw-bold">Revenue</span></li>

                            <li class="nav-item {{ request()->routeIs('reports.receipts*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('reports.receipts') }}">
                                    <span class="nav-link-icon"><i class="ti ti-wallet"></i></span>
                                    <span class="nav-link-title">Payments</span>
                                </a>
                            </li>

                            <li class="nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('transactions.index') }}">
                                    <span class="nav-link-icon"><i class="ti ti-receipt"></i></span>
                                    <span class="nav-link-title">Transactions</span>
                                </a>
                            </li>

                            @if(Auth::user()->role !== 'Sales Agent')
                                <li class="nav-item {{ request()->routeIs('billing.*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('billing.edit') }}">
                                        <span class="nav-link-icon"><i class="ti ti-file-invoice"></i></span>
                                        <span class="nav-link-title">Invoices</span>
                                    </a>
                                </li>
                            @endif

                            <li class="nav-item {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('expenses.index') }}">
                                    <span class="nav-link-icon"><i class="ti ti-cash-banknote"></i></span>
                                    <span class="nav-link-title">Expenses</span>
                                </a>
                            </li>

                            @php $reportsActive = request()->routeIs('reports.*') && ! request()->routeIs('reports.receipts*'); @endphp
                            <li class="nav-item {{ $reportsActive ? 'active' : '' }}">
                                <a class="nav-link" href="#rp-nav-reports" data-bs-toggle="collapse" role="button" aria-expanded="{{ $reportsActive ? 'true' : 'false' }}" aria-controls="rp-nav-reports">
                                    <span class="nav-link-icon"><i class="ti ti-report"></i></span>
                                    <span class="nav-link-title">Reports</span>
                                </a>
                                <div class="collapse {{ $reportsActive ? 'show' : '' }}" id="rp-nav-reports">
                                    <ul class="nav nav-pills flex-column ms-4">
                                        <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('reports.pppoe-balances') ? 'active' : '' }}" href="{{ route('reports.pppoe-balances') }}">Fixed User Balances</a></li>
                                        <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('reports.fixed-sales') ? 'active' : '' }}" href="{{ route('reports.fixed-sales') }}">Fixed Service Sales</a></li>
                                        <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('reports.hotspot-sales') ? 'active' : '' }}" href="{{ route('reports.hotspot-sales') }}">Hotspot Service Sales</a></li>
                                        <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('reports.access-log') ? 'active' : '' }}" href="{{ route('reports.access-log') }}">Access Requests</a></li>
                                        <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('reports.expired-users') ? 'active' : '' }}" href="{{ route('reports.expired-users') }}">Expired Users</a></li>
                                        <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('reports.analytics') ? 'active' : '' }}" href="{{ route('reports.analytics') }}">Analytics</a></li>
                                    </ul>
                                </div>
                            </li>

                            <li class="nav-item mt-3"><span class="nav-link-title px-2 text-uppercase small text-muted fw-bold">Comms</span></li>

                            <li class="nav-item {{ request()->routeIs('sms.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('sms.index') }}">
                                    <span class="nav-link-icon"><i class="ti ti-message-dots"></i></span>
                                    <span class="nav-link-title">SMS</span>
                                </a>
                            </li>

                            @if(Auth::user()->role !== 'Sales Agent')
                                <li class="nav-item {{ request()->routeIs('captive-announcements.*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('captive-announcements.index') }}">
                                        <span class="nav-link-icon"><i class="ti ti-bell"></i></span>
                                        <span class="nav-link-title">Announcements</span>
                                    </a>
                                </li>
                            @endif

                            <li class="nav-item mt-3"><span class="nav-link-title px-2 text-uppercase small text-muted fw-bold">Settings</span></li>

                            @if(Auth::user()->role === 'SuperAdmin')
                                <li class="nav-item {{ request()->routeIs('team.*') ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('team.index') }}">
                                        <span class="nav-link-icon"><i class="ti ti-lock"></i></span>
                                        <span class="nav-link-title">Access Control</span>
                                    </a>
                                </li>
                            @endif

                            <li class="nav-item {{ request()->routeIs('account.*') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('account.index') }}">
                                    <span class="nav-link-icon"><i class="ti ti-user-circle"></i></span>
                                    <span class="nav-link-title">Account</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </aside>

        <div class="page-wrapper">
            <header class="navbar navbar-expand-md navbar-light d-print-none">
                <div class="container-fluid">
                    <button type="button" class="btn btn-icon d-none d-lg-inline-flex" id="rp-sidebar-toggle" title="Collapse sidebar">
                        <i class="ti ti-layout-sidebar-left-collapse icon"></i>
                    </button>

                    @unless(Auth::user()->is_platform_admin)
                        <a href="{{ route('search') }}" class="btn btn-icon" title="Search">
                            <i class="ti ti-search icon"></i>
                        </a>
                    @endunless

                    <div class="navbar-nav flex-row order-md-last align-items-center gap-2">
                        <button type="button" class="nav-link px-2 d-none d-lg-inline-flex rp-theme-toggle" title="Toggle dark mode">
                            <i class="ti ti-moon icon"></i>
                        </button>

                        @php
                            $rpNotifConfig = [
                                'items' => Auth::user()->notifications()->latest()->limit(10)->get()->map(fn ($n) => [
                                    'id' => $n->id,
                                    'message' => $n->data['message'] ?? 'Notification',
                                    'url' => $n->data['url'] ?? null,
                                    'read' => (bool) $n->read_at,
                                    'created_at_human' => $n->created_at->diffForHumans(),
                                ]),
                                'unread' => Auth::user()->unreadNotifications->count(),
                                'markAllUrl' => route('notifications.mark-all-read'),
                            ];
                        @endphp
                        <script type="application/json" id="rp-notif-data">{{ Illuminate\Support\Js::from($rpNotifConfig) }}</script>

                        <div class="nav-item dropdown">
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
                                <span class="avatar avatar-sm bg-primary-lt">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
                                <div class="d-none d-xl-block ps-2">
                                    <div class="fw-bold">{{ Auth::user()->name }}</div>
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

    {{ $scripts ?? '' }}
    </body>
</html>
