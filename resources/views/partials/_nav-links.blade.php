{{-- Shared nav-item list — included once for the desktop rail (layouts/sidebar.blade.php's
     <aside>) and once for the mobile drawer (#rp-mobile-nav), so the two never drift apart.
     $navId ('desktop'|'mobile') keeps the two copies' collapsible-submenu ids unique, since
     both instances exist in the DOM at once (one just hidden via CSS) — without it, Bootstrap's
     data-bs-target on the mobile copy's "Platform"/"Reports" links would toggle the desktop
     copy's (first-in-DOM) submenu instead. --}}
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
            <a class="nav-link" href="#rp-nav-platform-{{ $navId }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('platform-admin.*') && ! request()->routeIs('platform-admin.dashboard') ? 'true' : 'false' }}" aria-controls="rp-nav-platform-{{ $navId }}">
                <span class="nav-link-icon"><i class="ti ti-shield-lock"></i></span>
                <span class="nav-link-title">Platform</span>
                @if($pendingCount > 0)
                    <span class="badge bg-red text-white ms-auto rp-nav-badge">{{ $pendingCount }}</span>
                @endif
            </a>
            <div class="collapse {{ request()->routeIs('platform-admin.*') && ! request()->routeIs('platform-admin.dashboard') ? 'show' : '' }}" id="rp-nav-platform-{{ $navId }}">
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
            <a class="nav-link" href="#rp-nav-reports-{{ $navId }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ $reportsActive ? 'true' : 'false' }}" aria-controls="rp-nav-reports-{{ $navId }}">
                <span class="nav-link-icon"><i class="ti ti-report"></i></span>
                <span class="nav-link-title">Reports</span>
            </a>
            <div class="collapse {{ $reportsActive ? 'show' : '' }}" id="rp-nav-reports-{{ $navId }}">
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
