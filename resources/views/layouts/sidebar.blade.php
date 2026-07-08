<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'RadiusPoint') }}{{ $title ? ' — '.$title : '' }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Figtree', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
                            fira: ['"Fira Code"', 'monospace'],
                        }
                    }
                }
            }
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    </head>
    <body>
    <div x-data="{ sidebarOpen: true, mobileMenu: false, darkMode: false }"
         :class="{ 'dark': darkMode }"
         class="flex h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-300 font-sans text-gray-800 dark:text-gray-200 overflow-hidden">

        <div x-show="mobileMenu" @click="mobileMenu = false" class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm lg:hidden"></div>

        <aside :class="sidebarOpen ? 'w-64' : 'w-20'"
               class="fixed lg:relative z-50 h-screen bg-white dark:bg-gray-950 shadow-xl lg:shadow-none border-r border-gray-200 dark:border-gray-800 transition-all duration-300 ease-in-out flex flex-col"
               x-bind:class="mobileMenu ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

            <div class="flex items-center justify-center h-16 border-b border-gray-200 dark:border-gray-800 px-4">
                <div class="flex items-center gap-3 overflow-hidden">
                    <x-application-logo class="h-8 w-8 shrink-0 text-blue-600 dark:text-blue-500" />
                    <span class="font-bold text-xl tracking-widest text-gray-900 dark:text-white whitespace-nowrap truncate" x-show="sidebarOpen">
                        {{ Auth::user()->is_platform_admin ? 'Platform Admin' : Auth::user()->tenant->company_name }}
                    </span>
                </div>
            </div>

            <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto custom-scrollbar">
                @if(Auth::user()->is_platform_admin)
                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 mt-2" x-show="sidebarOpen">Platform</p>
                    <div x-data="{ open: {{ request()->routeIs('platform-admin.*') ? 'true' : 'false' }} }" class="mt-1">
                        <button @click="open = !open" class="w-full flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                            <i class="bx bx-shield-quarter text-xl"></i>
                            <span class="ml-3 font-bold text-sm flex-1 text-left whitespace-nowrap" x-show="sidebarOpen">Platform</span>
                            @php
                                $pendingCount = \App\Models\Tenant::where('status', 'pending')->count();
                            @endphp
                            @if($pendingCount > 0)
                                <span class="bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full mr-2" x-show="sidebarOpen">{{ $pendingCount }}</span>
                            @endif
                            <i class="bx bx-chevron-down transition-transform" :class="open ? 'rotate-180' : ''" x-show="sidebarOpen"></i>
                        </button>
                        <div x-show="open && sidebarOpen" class="ml-9 mt-1 space-y-1">
                            <a href="{{ route('platform-admin.tenants.index') }}" class="block py-1.5 text-sm {{ request()->routeIs('platform-admin.tenants.index') ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400' }}">Tenants</a>
                            <a href="{{ route('platform-admin.tenants.import-form') }}" class="block py-1.5 text-sm {{ request()->routeIs('platform-admin.tenants.import-form') ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400' }}">Import Tenants</a>
                            <a href="{{ route('platform-admin.activity-log.index') }}" class="block py-1.5 text-sm {{ request()->routeIs('platform-admin.activity-log.index') ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400' }}">Activity Log</a>
                        </div>
                    </div>
                @endif

                @unless(Auth::user()->is_platform_admin)
                <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 mt-2" x-show="sidebarOpen">Main</p>
                <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <i class="bx bxs-dashboard text-xl"></i>
                    <span class="ml-3 font-bold text-sm whitespace-nowrap" x-show="sidebarOpen">Dashboard</span>
                </a>

                <div x-data="{ open: {{ request()->routeIs(['pppoe-users.*', 'hotspot-users.*', 'leads.*']) ? 'true' : 'false' }} }" class="mt-1">
                    <button @click="open = !open" class="w-full flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                        <i class="bx bx-group text-xl"></i>
                        <span class="ml-3 font-medium text-sm flex-1 text-left whitespace-nowrap" x-show="sidebarOpen">Customers</span>
                        <i class="bx bx-chevron-down transition-transform" :class="open ? 'rotate-180' : ''" x-show="sidebarOpen"></i>
                    </button>
                    <div x-show="open && sidebarOpen" class="ml-9 mt-1 space-y-1">
                        <a href="{{ route('pppoe-users.index') }}" class="block py-1.5 text-sm {{ request()->routeIs('pppoe-users.*') ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400' }}">PPPoE Users</a>
                        <a href="{{ route('hotspot-users.index') }}" class="block py-1.5 text-sm {{ request()->routeIs('hotspot-users.*') ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400' }}">Hotspot Users</a>
                        <a href="{{ route('leads.index') }}" class="block py-1.5 text-sm {{ request()->routeIs('leads.*') ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400' }}">Leads</a>
                    </div>
                </div>

                <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 mt-6" x-show="sidebarOpen">Network</p>
                <a href="{{ route('routers.index') }}" class="flex items-center px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('routers.*') ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <i class="bx bx-server text-xl"></i>
                    <span class="ml-3 font-medium text-sm whitespace-nowrap" x-show="sidebarOpen">Hardware &amp; Routers</span>
                </a>

                <a href="{{ route('plans.index') }}" class="flex items-center px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('plans.*') ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <i class="bx bx-box text-xl"></i>
                    <span class="ml-3 font-medium text-sm whitespace-nowrap" x-show="sidebarOpen">Packages &amp; Plans</span>
                </a>

                <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 mt-6" x-show="sidebarOpen">Finance</p>
                <a href="{{ route('transactions.index') }}" class="flex items-center px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('transactions.*') ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <i class="bx bx-receipt text-xl"></i>
                    <span class="ml-3 font-medium text-sm whitespace-nowrap" x-show="sidebarOpen">Transactions</span>
                </a>

                <a href="{{ route('vouchers.index') }}" class="flex items-center px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('vouchers.*') ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <i class="bx bx-barcode-reader text-xl"></i>
                    <span class="ml-3 font-medium text-sm whitespace-nowrap" x-show="sidebarOpen">Vouchers</span>
                </a>

                @if(Auth::user()->role !== 'Sales Agent')
                    <a href="{{ route('mpesa-settings.edit') }}" class="flex items-center px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('mpesa-settings.*') ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <i class="bx bx-credit-card text-xl"></i>
                        <span class="ml-3 font-medium text-sm whitespace-nowrap" x-show="sidebarOpen">M-Pesa Settings</span>
                    </a>
                @endif

                @if(Auth::user()->role === 'SuperAdmin')
                    <a href="{{ route('team.index') }}" class="flex items-center px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('team.*') ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <i class="bx bx-id-card text-xl"></i>
                        <span class="ml-3 font-medium text-sm whitespace-nowrap" x-show="sidebarOpen">Team</span>
                    </a>
                @endif

                <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 mt-6" x-show="sidebarOpen">Support</p>
                <a href="{{ route('tickets.index') }}" class="flex items-center px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('tickets.*') ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <i class="bx bx-support text-xl"></i>
                    <span class="ml-3 font-medium text-sm whitespace-nowrap" x-show="sidebarOpen">Support Tickets</span>
                </a>

                <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 mt-6" x-show="sidebarOpen">Communication</p>
                <a href="{{ route('sms.index') }}" class="flex items-center px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('sms.*') ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <i class="bx bx-message-square-dots text-xl"></i>
                    <span class="ml-3 font-medium text-sm whitespace-nowrap" x-show="sidebarOpen">SMS Outbox</span>
                </a>

                <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 mt-6" x-show="sidebarOpen">Reports</p>
                <div x-data="{ open: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }" class="mt-1">
                    <button @click="open = !open" class="w-full flex items-center px-3 py-2.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                        <i class="bx bxs-report text-xl"></i>
                        <span class="ml-3 font-medium text-sm flex-1 text-left whitespace-nowrap" x-show="sidebarOpen">Reports</span>
                        <i class="bx bx-chevron-down transition-transform" :class="open ? 'rotate-180' : ''" x-show="sidebarOpen"></i>
                    </button>
                    <div x-show="open && sidebarOpen" class="ml-9 mt-1 space-y-1">
                        <a href="{{ route('reports.pppoe-balances') }}" class="block py-1.5 text-sm {{ request()->routeIs('reports.pppoe-balances') ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400' }}">Fixed User Balances</a>
                        <a href="{{ route('reports.fixed-sales') }}" class="block py-1.5 text-sm {{ request()->routeIs('reports.fixed-sales') ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400' }}">Fixed Service Sales</a>
                        <a href="{{ route('reports.hotspot-sales') }}" class="block py-1.5 text-sm {{ request()->routeIs('reports.hotspot-sales') ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400' }}">Hotspot Service Sales</a>
                        <a href="{{ route('reports.access-log') }}" class="block py-1.5 text-sm {{ request()->routeIs('reports.access-log') ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400' }}">Access Requests</a>
                    </div>
                </div>
                @endunless
            </nav>
        </aside>

        <main class="flex-1 flex flex-col h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">

            <header class="h-16 bg-white dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between px-4 lg:px-8 z-30">
                <div class="flex items-center gap-4">
                    <button @click="mobileMenu = true" class="lg:hidden text-gray-500 hover:text-blue-600">
                        <i class="bx bx-menu text-2xl"></i>
                    </button>
                    <button @click="sidebarOpen = !sidebarOpen" class="hidden lg:block text-gray-500 hover:text-blue-600 transition-colors">
                        <i class="bx text-2xl" :class="sidebarOpen ? 'bx-menu-alt-left' : 'bx-menu'"></i>
                    </button>

                    <div class="hidden md:flex items-center bg-gray-100 dark:bg-gray-800 rounded-lg px-3 py-1.5 border border-transparent focus-within:border-blue-500 transition-colors">
                        <i class="bx bx-search text-gray-400 text-lg"></i>
                        <input type="text" placeholder="Search users, IPs, or invoices..." class="bg-transparent border-none focus:ring-0 text-sm ml-2 w-64 dark:text-gray-200 dark:placeholder-gray-500">
                    </div>
                </div>

                @php
                    // Computed here (not inline inside the x-data attribute below) — a
                    // multi-line @json() call wrapping a closure with its own [...] array
                    // literal confused Blade's directive-argument parser: it compiled without
                    // error but produced truncated, syntactically broken PHP that only failed
                    // at actual render time. A plain variable sidesteps that entirely.
                    $notifBellItems = Auth::user()->notifications()->latest()->limit(10)->get()->map(fn ($n) => [
                        'id' => $n->id,
                        'message' => $n->data['message'] ?? 'Notification',
                        'url' => $n->data['url'] ?? null,
                        'read' => (bool) $n->read_at,
                        'created_at_human' => $n->created_at->diffForHumans(),
                    ]);
                @endphp
                <div class="flex items-center gap-4 lg:gap-6">
                    <button @click="darkMode = !darkMode" class="text-gray-500 hover:text-yellow-500 dark:hover:text-yellow-400 transition-colors focus:outline-none">
                        <i class="bx text-2xl" :class="darkMode ? 'bx-sun' : 'bx-moon'"></i>
                    </button>
                    <div x-data="{
                            notifOpen: false,
                            unread: {{ Auth::user()->unreadNotifications->count() }},
                            notifications: {{ Illuminate\Support\Js::from($notifBellItems) }},

                            startPolling() {
                                // 20s — frequent enough that a router-down alert feels current
                                // without hammering the endpoint on every single page nav.
                                setInterval(() => this.poll(), 20000);
                            },

                            async poll() {
                                try {
                                    const res = await fetch('{{ route('notifications.recent') }}', { headers: { 'Accept': 'application/json' } });
                                    const data = await res.json();
                                    this.unread = data.unread_count;
                                    this.notifications = data.notifications;
                                } catch (e) {
                                    // Next tick retries — badge just stays stale meanwhile.
                                }
                            },

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
                         }"
                         x-init="startPolling()"
                         class="relative">
                        <button @click="notifOpen = !notifOpen" class="relative text-gray-500 hover:text-blue-600 transition-colors">
                            <i class="bx bx-bell text-2xl"></i>
                            <span x-show="unread > 0" x-cloak class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full border border-white dark:border-gray-950"></span>
                        </button>
                        {{-- max-w-[90vw] keeps this from overflowing the viewport on narrow screens, since a fixed w-80 (320px) can exceed the visible width entirely on small phones. --}}
                        <div x-show="notifOpen" x-cloak x-transition @click.outside="notifOpen = false" class="absolute right-0 mt-2 w-80 max-w-[90vw] bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg shadow-lg py-1 z-50 max-h-96 overflow-y-auto">
                            <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100 dark:border-gray-800 sticky top-0 bg-white dark:bg-gray-950">
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Notifications</p>
                                <a href="{{ route('settings.notifications.edit') }}" class="text-[10px] font-bold text-gray-400 hover:text-blue-600 uppercase tracking-wide">Preferences</a>
                            </div>
                            <template x-if="notifications.length === 0">
                                <p class="px-4 py-6 text-center text-xs text-gray-400">No notifications yet.</p>
                            </template>
                            <template x-for="n in notifications" :key="n.id">
                                <button type="button" @click="openNotification(n)" class="w-full text-left px-4 py-3 text-sm border-b border-gray-50 dark:border-gray-900 last:border-0 flex items-start gap-2 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors" :class="!n.read ? 'bg-blue-50 dark:bg-blue-900/10' : ''">
                                    <span class="w-1.5 h-1.5 rounded-full mt-1.5 shrink-0" :class="!n.read ? 'bg-blue-500' : ''"></span>
                                    <div>
                                        <p class="text-gray-700 dark:text-gray-300" x-text="n.message"></p>
                                        <p class="text-[10px] text-gray-400 mt-1" x-text="n.created_at_human"></p>
                                    </div>
                                </button>
                            </template>
                            <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-2">
                                <a href="{{ route('notifications.index') }}" class="text-[10px] font-bold text-gray-500 hover:text-blue-600 uppercase tracking-wide">View All</a>
                                <button type="button" x-show="unread > 0" @click="markAllRead()" class="text-[10px] font-bold text-blue-600 hover:text-blue-700 uppercase tracking-wide">Mark all read</button>
                            </div>
                        </div>
                    </div>
                    <div class="h-8 w-px bg-gray-200 dark:bg-gray-700 hidden sm:block"></div>
                    <div x-data="{ profileOpen: false }" class="relative">
                        <button @click="profileOpen = !profileOpen" @click.outside="profileOpen = false" class="flex items-center gap-3 cursor-pointer">
                            <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-md">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                            <div class="hidden sm:block text-left">
                                <p class="text-sm font-bold text-gray-900 dark:text-white leading-tight">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ Auth::user()->is_platform_admin ? 'Platform Admin' : Auth::user()->tenant->company_name }}</p>
                            </div>
                            <i class="bx bx-chevron-down text-gray-400 hidden sm:block"></i>
                        </button>
                        <div x-show="profileOpen" x-cloak x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg shadow-lg py-1 z-50">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">Profile</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">Log Out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-4 lg:p-8">
                @if (session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="mb-6 flex items-center justify-between gap-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-900/50 text-green-700 dark:text-green-400 text-sm font-bold px-4 py-3 rounded-lg">
                        <span class="flex items-center gap-2"><i class="bx bxs-check-circle text-lg"></i> {{ session('success') }}</span>
                        <button @click="show = false" class="text-green-500 hover:text-green-700"><i class="bx bx-x text-lg"></i></button>
                    </div>
                @endif
                @if (session('error'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="mb-6 flex items-center justify-between gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-900/50 text-red-700 dark:text-red-400 text-sm font-bold px-4 py-3 rounded-lg">
                        <span class="flex items-center gap-2"><i class="bx bxs-error-circle text-lg"></i> {{ session('error') }}</span>
                        <button @click="show = false" class="text-red-500 hover:text-red-700"><i class="bx bx-x text-lg"></i></button>
                    </div>
                @endif
                {{ $slot }}
            </div>
        </main>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{ $scripts ?? '' }}

    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #374151; }
    </style>
    </body>
</html>
