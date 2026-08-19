<x-sidebar-layout title="Provision Router">
    {{-- Tailwind's CDN runtime only generates CSS for classes present at page load; these are
         swapped in dynamically via JS classList.replace(), so pre-declare them here (hidden) to
         force the CDN compiler to generate the rules upfront. --}}
    <div class="hidden bg-blue-500 bg-green-500 bg-red-500 text-blue-600 text-green-600 text-red-600"></div>

    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Zero-Touch Uplink Protocol</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Target Hardware: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $router->name }}</span> &middot; IP: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $router->ip_address }}</span>
            </p>
        </div>
        <div class="flex items-center gap-3 bg-amber-50 dark:bg-amber-900/20 px-4 py-2 rounded-lg border border-amber-200 dark:border-amber-900/50">
            <div id="statusPulse" class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping"></div>
            <span id="statusText" class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wide">Waiting for Router</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <div class="lg:col-span-5 bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 flex items-center justify-between border-b border-gray-200 dark:border-gray-800">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Setup Script</span>
                <button onclick="copyPayload()" id="copyBtn" class="text-xs font-bold bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1.5 rounded-md transition-colors flex items-center gap-2 border border-blue-100">
                    <i class='bx bx-copy'></i> Copy Payload
                </button>
            </div>

            <div class="p-5 flex-1">
                <p class="text-gray-400 text-xs mb-3 uppercase tracking-wide border-b border-gray-100 dark:border-gray-800 pb-2 flex items-center gap-2">
                    <i class='bx bx-terminal'></i> Paste this sequence into MikroTik Terminal
                </p>
                <div class="bg-gray-900 rounded-lg p-4 overflow-y-auto max-h-80 custom-scrollbar">
                    <pre id="payloadText" class="text-green-400 text-[12px] leading-loose whitespace-pre-wrap font-fira">{{ trim($script) }}</pre>
                </div>

                <div class="mt-4" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                        <i class='bx bx-help-circle text-base'></i> Can't find Terminal to paste this?
                        <i class='bx bx-chevron-down transition-transform' :class="open && 'rotate-180'"></i>
                    </button>
                    <div x-show="open" x-cloak class="mt-3 text-xs text-gray-500 dark:text-gray-400 leading-relaxed bg-gray-50 dark:bg-gray-900/60 rounded-lg p-3 border border-gray-100 dark:border-gray-800">
                        A factory-fresh router often boots into Winbox's <strong>Quick Set</strong> screen instead of the full menu, which doesn't show Terminal by default. Look for the mode toggle in the top-right corner of the Winbox login window and switch it from <strong>Quick Set</strong> to <strong>Winbox</strong> &mdash; Terminal will then be in the left-hand menu.
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-7 bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col overflow-hidden">

            <div class="h-40 flex items-center justify-center relative overflow-hidden border-b border-gray-200 dark:border-gray-800 bg-gray-900">
                <div class="absolute w-56 h-56 border border-blue-500/10 rounded-full"></div>
                <div class="absolute w-40 h-40 border border-blue-500/20 rounded-full"></div>
                <div class="absolute w-24 h-24 border border-blue-500/30 rounded-full"></div>

                <div id="radarNode" class="w-4 h-4 bg-amber-500 rounded-full z-10 transition-colors duration-500"></div>

                <div id="radarSweep" class="absolute w-24 h-24 origin-bottom-right border-r-2 border-blue-400/80 bg-gradient-to-tr from-transparent to-blue-500/20 right-1/2 bottom-1/2 animate-[spin_3s_linear_infinite] hidden"></div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900 px-5 py-3 border-b border-gray-200 dark:border-gray-800">
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wide mb-2">
                    <span>Uplink Progress</span>
                    <span id="progressText">0%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-1.5">
                    <div id="progressBar" class="bg-blue-500 h-1.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>

            <div class="flex-1 p-5 flex flex-col justify-end">
                <div id="liveLogs" class="space-y-2 mb-5 flex flex-col justify-end min-h-[120px] bg-gray-900 rounded-lg p-4 font-fira text-[11px] leading-relaxed">
                    <div class="text-gray-500">&gt; System in standby state...</div>
                    <div class="text-gray-500">&gt; Watching for the script to complete on your router — no action needed here.</div>
                    <div id="lastCheckLog" class="text-blue-400 animate-pulse">&gt; _</div>
                </div>

                <button id="initiateBtn" onclick="executeHandshake(true)" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold uppercase tracking-wide py-4 rounded-lg shadow-sm transition-all flex items-center justify-center gap-3">
                    <i class='bx bx-refresh text-xl'></i>
                    <span>Check Now</span>
                </button>

                <a href="{{ route('routers.ports', $router->id) }}" id="proceedBtn" class="hidden w-full bg-green-600 hover:bg-green-700 text-white font-bold uppercase tracking-wide py-4 rounded-lg shadow-sm transition-all items-center justify-center gap-3">
                    Hardware Verified &mdash; Map Ports <i class='bx bx-right-arrow-alt text-xl'></i>
                </a>
            </div>
        </div>

    </div>

    <x-slot name="scripts">
        <script>
            function copyPayload() {
                navigator.clipboard.writeText(document.getElementById('payloadText').innerText);
                const btn = document.getElementById('copyBtn');
                btn.innerHTML = "<i class='bx bx-check'></i> Copied to Clipboard";
                btn.classList.replace('text-blue-600', 'text-green-700');
                btn.classList.replace('bg-blue-50', 'bg-green-50');
                btn.classList.replace('border-blue-100', 'border-green-200');

                setTimeout(() => {
                    btn.innerHTML = "<i class='bx bx-copy'></i> Copy Payload";
                    btn.classList.replace('text-green-700', 'text-blue-600');
                    btn.classList.replace('bg-green-50', 'bg-blue-50');
                    btn.classList.replace('border-green-200', 'border-blue-100');
                }, 3000);
            }

            let pollTimer = null;
            let verified = false;

            function startPolling() {
                pollTimer = setInterval(() => executeHandshake(false), 4000);
            }

            async function executeHandshake(manual) {
                if (verified) return;

                const btn = document.getElementById('initiateBtn');
                const logsBox = document.getElementById('liveLogs');
                const lastCheckLog = document.getElementById('lastCheckLog');
                const progressBar = document.getElementById('progressBar');
                const progressText = document.getElementById('progressText');
                const statusText = document.getElementById('statusText');
                const statusPulse = document.getElementById('statusPulse');
                const radarSweep = document.getElementById('radarSweep');
                const radarNode = document.getElementById('radarNode');

                if (manual) {
                    btn.classList.add('pointer-events-none', 'opacity-50');
                    btn.querySelector('span').innerText = "Checking...";
                    btn.querySelector('i').className = 'bx bx-loader-alt bx-spin text-xl';

                    statusText.innerText = "SCANNING NETWORK";
                    statusText.classList.replace('text-amber-600', 'text-blue-600');
                    statusPulse.classList.replace('bg-amber-500', 'bg-blue-500');

                    radarSweep.classList.remove('hidden');
                    radarNode.classList.replace('bg-amber-500', 'bg-blue-500');
                    progressBar.style.width = "50%";
                }

                try {
                    const response = await fetch("{{ route('routers.check-status', $router->id) }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        }
                    });
                    const data = await response.json();

                    if (data.status === 'success') {
                        // SUCCESS STATE — reached the same way whether this was the manual
                        // button or a background poll tick that happened to land first.
                        verified = true;
                        if (pollTimer) clearInterval(pollTimer);

                        progressBar.style.width = "100%";
                        progressText.innerText = "100%";
                        progressBar.classList.replace('bg-blue-500', 'bg-green-500');

                        radarSweep.classList.add('hidden');
                        radarNode.classList.replace('bg-blue-500', 'bg-green-500');
                        radarNode.classList.replace('bg-amber-500', 'bg-green-500');

                        let finalLog = document.createElement('div');
                        finalLog.innerHTML = "&gt; <span class='text-green-400 font-bold'>[UPLINK VERIFIED] Hardware is online.</span>";
                        logsBox.appendChild(finalLog);

                        statusText.innerText = "HARDWARE ONLINE";
                        statusText.classList.remove('text-blue-600', 'text-amber-600');
                        statusText.classList.add('text-green-600');
                        statusPulse.classList.remove('bg-blue-500', 'bg-amber-500', 'animate-ping');
                        statusPulse.classList.add('bg-green-500');

                        btn.classList.add('hidden');
                        document.getElementById('proceedBtn').classList.replace('hidden', 'flex');

                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    if (manual) {
                        // FAILED STATE — only shown for a manual check. A background poll tick
                        // that hasn't succeeded yet just means the router isn't there yet, not
                        // that anything has actually failed, so it stays quiet.
                        progressBar.classList.replace('bg-blue-500', 'bg-red-500');
                        radarSweep.classList.add('hidden');
                        radarNode.classList.replace('bg-blue-500', 'bg-red-500');

                        let finalLog = document.createElement('div');
                        finalLog.innerHTML = "&gt; <span class='text-red-400 font-bold'>[NOT YET] " + (error.message || "Hardware not reachable yet.") + "</span>";
                        logsBox.appendChild(finalLog);

                        statusText.innerText = "WAITING FOR ROUTER";
                        statusText.classList.replace('text-blue-600', 'text-amber-600');
                        statusPulse.classList.replace('bg-blue-500', 'bg-amber-500');

                        btn.classList.remove('pointer-events-none', 'opacity-50');
                        btn.querySelector('span').innerText = "Check Now";
                        btn.querySelector('i').className = 'bx bx-refresh text-xl';
                    } else {
                        lastCheckLog.innerHTML = "&gt; Still waiting... last checked " + new Date().toLocaleTimeString();
                    }
                }
            }

            startPolling();
        </script>

        <style>
            .animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateX(-10px); }
                to { opacity: 1; transform: translateX(0); }
            }
            .custom-scrollbar::-webkit-scrollbar { width: 4px; }
            .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
            .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.4); border-radius: 10px; }
        </style>
    </x-slot>
</x-sidebar-layout>
