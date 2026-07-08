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
<body class="font-sans min-h-screen flex items-center justify-center p-4" style="background: linear-gradient(135deg, {{ $portal->primary_color ?? '#2563eb' }}, #111827)">

    <div x-data="captivePortal()" class="w-full max-w-md">
        <div class="text-center mb-4 text-white">
            @if($portal?->logo_url)
                <img src="{{ $portal->logo_url }}" alt="{{ $tenant->company_name }}" class="h-12 mx-auto mb-2 object-contain">
            @endif
            <h1 class="text-2xl font-bold">{{ $tenant->company_name }}</h1>
        </div>

        @if($portal?->notice_title || $portal?->notice_body)
            <div class="bg-white/10 backdrop-blur border border-white/20 text-white rounded-xl px-4 py-3 mb-4 text-center">
                @if($portal->notice_title)
                    <p class="text-xs font-bold uppercase tracking-wide">{{ $portal->notice_title }}</p>
                @endif
                @if($portal->notice_body)
                    <p class="text-sm text-white/80 mt-1">{{ $portal->notice_body }}</p>
                @endif
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            @if($plans->isNotEmpty())
                <div class="p-5 bg-gray-900 text-white text-center">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 mb-1">Best value today</p>
                    <p class="text-lg font-bold">{{ $plans->first()->name }} — KES {{ number_format($plans->first()->price) }}</p>
                </div>
            @endif

            <div class="p-6 space-y-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Already paid? Reconnect</p>
                    <div class="flex gap-2">
                        <input type="tel" x-model="phone" placeholder="0712345678" :disabled="looking"
                               class="flex-1 border border-gray-200 rounded-lg py-3 px-4 outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="button" @click="lookup()" :disabled="!phone || looking"
                                class="bg-gray-900 hover:bg-gray-800 disabled:opacity-50 text-white font-bold px-5 rounded-lg transition-colors">
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

                <a href="{{ route('portal.show', $router) }}" class="block w-full text-center text-white font-bold py-3.5 rounded-lg shadow-md transition-transform hover:scale-[1.02]" style="background: {{ $portal->primary_color ?? '#2563eb' }}">
                    🔥 Get Connected Now
                </a>
            </div>
        </div>
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
