<x-sidebar-layout title="Port Mapping">
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <span class="px-2 py-0.5 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-900/50 text-green-700 dark:text-green-400 text-[10px] uppercase tracking-wide rounded font-bold">
                Uplink Secured
            </span>
            <span class="text-[10px] text-gray-400 tracking-wide uppercase">Target: {{ $router->ip_address }}</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Network Topology Mapping</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Live hardware interfaces detected. Assign service protocols to physical ports.</p>
    </div>

    <form action="{{ route('routers.save-ports', $router->id) }}" method="POST" class="space-y-6 max-w-5xl">
        @csrf

        <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">

            <div class="grid grid-cols-12 gap-4 px-6 py-4 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                <div class="col-span-1 text-center">Status</div>
                <div class="col-span-5">Physical Interface</div>
                <div class="col-span-6">Service Assignment</div>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800 p-2">
                @php $validInterfacesFound = false; @endphp

                @foreach($interfaces as $interface)
                    @if(in_array($interface['type'] ?? 'ether', ['ether', 'wlan', 'bridge', 'vlan']))
                        @php $validInterfacesFound = true; @endphp

                        <div class="grid grid-cols-12 gap-4 items-center px-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors rounded-lg group">

                            <div class="col-span-1 flex justify-center">
                                @if(isset($interface['running']) && $interface['running'] == 'true')
                                    <div class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse" title="Port is Active/Running"></div>
                                @else
                                    <div class="w-2.5 h-2.5 rounded-full bg-gray-300 dark:bg-gray-700" title="Port is Inactive"></div>
                                @endif
                            </div>

                            <div class="col-span-5 flex flex-col">
                                <div class="flex items-center gap-3">
                                    <i class='bx {{ $interface['type'] == 'wlan' ? 'bx-wifi' : 'bx-plug' }} text-xl text-blue-500'></i>
                                    <span class="text-gray-900 dark:text-white font-bold text-sm">{{ $interface['name'] }}</span>
                                </div>
                                @if(isset($interface['mac-address']))
                                    <span class="text-[10px] text-gray-400 mt-1 uppercase tracking-wide ml-8 pl-0.5">MAC: {{ $interface['mac-address'] }}</span>
                                @endif
                            </div>

                            <div class="col-span-6">
                                <select name="ports[{{ $interface['name'] }}]" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm py-2.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer outline-none transition-colors">
                                    <option value="none">Unassigned / Bypass</option>
                                    <option value="hotspot">Hotspot Gateway</option>
                                    <option value="pppoe">PPPoE Server</option>
                                    <option value="both">Hybrid (Hotspot + PPPoE)</option>
                                </select>
                            </div>

                        </div>
                    @endif
                @endforeach

                @if(!$validInterfacesFound)
                    <div class="p-8 text-center flex flex-col items-center justify-center text-gray-400">
                        <i class='bx bx-error-alt text-4xl mb-3 text-amber-400'></i>
                        <p class="text-xs tracking-wide uppercase">No compatible Ethernet or WLAN interfaces detected on target hardware.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex items-center justify-between p-6 bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/40 rounded-xl">
            <div>
                <p class="text-[10px] text-blue-700 dark:text-blue-400 uppercase tracking-wide font-bold mb-1">Before you continue</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Applying this configuration will finalize the hardware deployment and register the router to your central dashboard.</p>
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors inline-flex items-center gap-2 shrink-0">
                <i class='bx bx-lock-alt text-lg'></i> Lock Config &amp; Deploy
            </button>
        </div>

    </form>
</x-sidebar-layout>
