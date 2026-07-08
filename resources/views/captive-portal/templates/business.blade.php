<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $tenant->company_name }} — WiFi</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Figtree', 'ui-sans-serif', 'system-ui'] } } } }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body class="font-sans bg-white min-h-screen">

    <div x-data="captivePortal()">
        <header class="border-b border-gray-100 px-6 py-4 flex items-center justify-between" style="border-top: 4px solid {{ $portal->primary_color ?? '#2563eb' }}">
            <div class="flex items-center gap-3">
                @if($portal?->logo_url)
                    <img src="{{ $portal->logo_url }}" alt="{{ $tenant->company_name }}" class="h-8 object-contain">
                @else
                    <i class="bx bx-broadcast text-2xl" style="color: {{ $portal->primary_color ?? '#2563eb' }}"></i>
                @endif
                <span class="font-bold text-gray-900">{{ $tenant->company_name }}</span>
            </div>
            <span class="text-xs text-gray-400 uppercase tracking-widest">Guest WiFi</span>
        </header>

        @if($portal?->notice_title || $portal?->notice_body)
            <div class="bg-amber-50 border-b border-amber-100 px-6 py-3 text-center">
                @if($portal->notice_title)
                    <span class="text-xs font-bold text-amber-800 uppercase tracking-wide">{{ $portal->notice_title }}</span>
                @endif
                @if($portal->notice_body)
                    <span class="text-sm text-amber-700 ml-2">{{ $portal->notice_body }}</span>
                @endif
            </div>
        @endif

        <main class="max-w-3xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-2 gap-10">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-1">Already a customer?</h1>
                <p class="text-sm text-gray-500 mb-4">Enter your phone number to reconnect instantly.</p>
                <div class="flex gap-2">
                    <input type="tel" x-model="phone" placeholder="0712345678" :disabled="looking"
                           class="flex-1 border border-gray-200 rounded-lg py-3 px-4 outline-none focus:ring-2" style="--tw-ring-color: {{ $portal->primary_color ?? '#2563eb' }}">
                    <button type="button" @click="lookup()" :disabled="!phone || looking"
                            class="text-white font-bold px-5 rounded-lg transition-colors" style="background: {{ $portal->primary_color ?? '#2563eb' }}">
                        <i class="bx" :class="looking ? 'bx-loader-alt bx-spin' : 'bx-search'"></i>
                    </button>
                </div>
                <p x-show="lookupMessage" x-text="lookupMessage" class="text-sm mt-2" :class="found ? 'text-green-600' : 'text-red-500'"></p>

                <template x-if="found">
                    <div class="mt-3 space-y-3">
                        <button type="button" @click="autoConnect()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition-colors">
                            Connect Me Automatically
                        </button>
                        <div class="bg-gray-50 rounded-lg p-4 text-left space-y-1">
                            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Or enter manually — Username</p>
                            <p class="font-bold font-mono text-sm" x-text="username"></p>
                            <p class="text-[10px] text-gray-500 uppercase tracking-wide mt-2">Password</p>
                            <p class="font-bold font-mono text-sm" x-text="password"></p>
                        </div>
                    </div>
                </template>
            </div>

            <div class="bg-gray-50 rounded-2xl p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1">New here?</h2>
                <p class="text-sm text-gray-500 mb-4">Buy a plan and get connected in seconds.</p>
                <a href="{{ route('portal.show', $router) }}" class="block w-full text-center text-white font-bold py-3 rounded-lg transition-colors" style="background: {{ $portal->primary_color ?? '#2563eb' }}">
                    View Plans &amp; Pay
                </a>
            </div>
        </main>
    </div>

    <form id="rp-hotspot-login-form" method="post" :action="linkLoginOnly" class="hidden">
        <input type="hidden" name="username" x-bind:value="username">
        <input type="hidden" name="password" x-bind:value="password">
    </form>

    <script>
        function captivePortal() {
            return {
                phone: '',
                looking: false,
                found: false,
                username: null,
                password: null,
                lookupMessage: null,
                linkLoginOnly: @json($linkLoginOnly),

                async lookup() {
                    this.looking = true;
                    this.lookupMessage = null;
                    this.found = false;
                    try {
                        const response = await fetch("{{ route('captive.lookup', $router) }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ phone: this.phone }),
                        });
                        const data = await response.json();
                        if (data.found) {
                            this.found = true;
                            this.username = data.username;
                            this.password = data.password;
                            this.lookupMessage = 'Found your plan!';
                        } else {
                            this.lookupMessage = data.message || 'No active plan found for this number.';
                        }
                    } catch (e) {
                        this.lookupMessage = 'Network error — please try again.';
                    }
                    this.looking = false;
                },

                autoConnect() {
                    if (!this.linkLoginOnly) {
                        this.lookupMessage = 'Could not detect the router login page — please enter the details manually in your WiFi login screen.';
                        return;
                    }
                    document.getElementById('rp-hotspot-login-form').submit();
                },
            };
        }
    </script>
</body>
</html>
