<x-sidebar-layout title="Port Mapping">
    <div class="mb-4">
        <div class="d-flex align-items-center gap-3 mb-2">
            <span class="badge bg-green-lt">Connected</span>
            <span class="text-muted text-uppercase" style="font-size:.625rem">{{ $router->ip_address }}</span>
        </div>
        <h1 class="mb-1">Map Ports</h1>
        <p class="text-muted mb-0">Assign a service to each port on the hardware.</p>
    </div>

    <form action="{{ route('routers.save-ports', $router->id) }}" method="POST" style="max-width:64rem">
        @csrf

        <div class="card mb-3">
            <div class="row g-2 px-3 py-3 bg-body-secondary border-bottom text-muted text-uppercase small fw-bold mx-0">
                <div class="col-1 text-center">Status</div>
                <div class="col-5">Physical Interface</div>
                <div class="col-3 text-center">Hotspot</div>
                <div class="col-3 text-center">PPPoE</div>
            </div>

            <div class="list-group list-group-flush">
                @php $validInterfacesFound = false; @endphp

                @foreach($interfaces as $interface)
                    @if(in_array($interface['type'] ?? 'ether', ['ether', 'wlan', 'bridge', 'vlan']))
                        @php $validInterfacesFound = true; @endphp

                        {{-- Tracks every shown interface so savePorts() can explicitly set unchecked ones to "none". --}}
                        <input type="hidden" name="all_interfaces[]" value="{{ $interface['name'] }}">

                        @php
                            $existingRole = $router->port_configuration[$interface['name']]['role'] ?? 'none';
                        @endphp
                        <div class="row g-2 align-items-center px-3 py-3 list-group-item" data-rp-port-row>
                            <div class="col-1 text-center">
                                @if(isset($interface['running']) && $interface['running'] == 'true')
                                    <span class="badge bg-green" style="width:.625rem;height:.625rem;padding:0;border-radius:50%" title="Port is Active/Running"></span>
                                @else
                                    <span class="badge bg-secondary" style="width:.625rem;height:.625rem;padding:0;border-radius:50%" title="Port is Inactive"></span>
                                @endif
                            </div>

                            <div class="col-5">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ti {{ $interface['type'] == 'wlan' ? 'ti-wifi' : 'ti-plug' }} text-primary"></i>
                                    <span class="fw-bold">{{ $interface['name'] }}</span>
                                    {{-- A port with neither box checked is left exactly as-is — not disabled, not
                                         reassigned, free for its normal DHCP/manual use. This just makes that
                                         explicit rather than leaving it to look unfinished. --}}
                                    <span class="badge bg-secondary-lt" data-rp-port-dynamic @if(in_array($existingRole, ['hotspot', 'both', 'pppoe'])) style="display:none" @endif>Dynamic</span>
                                </div>
                                @if(isset($interface['mac-address']))
                                    <span class="text-muted text-uppercase ms-4 ps-1" style="font-size:.625rem">MAC: {{ $interface['mac-address'] }}</span>
                                @endif
                            </div>

                            <div class="col-3 text-center">
                                <label class="form-check d-inline-flex justify-content-center">
                                    <input type="checkbox" name="hotspot_ports[]" value="{{ $interface['name'] }}" class="form-check-input" data-rp-port-toggle @checked(in_array($existingRole, ['hotspot', 'both']))>
                                    <span class="form-check-label d-sm-none">Hotspot</span>
                                </label>
                            </div>

                            <div class="col-3 text-center">
                                <label class="form-check d-inline-flex justify-content-center">
                                    <input type="checkbox" name="pppoe_ports[]" value="{{ $interface['name'] }}" class="form-check-input" data-rp-port-toggle @checked(in_array($existingRole, ['pppoe', 'both']))>
                                    <span class="form-check-label d-sm-none">PPPoE</span>
                                </label>
                            </div>
                        </div>
                    @endif
                @endforeach

                @if(!$validInterfacesFound)
                    <div class="p-5 text-center text-muted">
                        <i class="ti ti-alert-triangle icon icon-lg text-warning mb-2 d-block"></i>
                        <p class="text-uppercase small mb-0">No compatible Ethernet or WLAN interfaces detected on target hardware.</p>
                    </div>
                @endif
            </div>
            <p class="text-muted small px-3 py-2 border-top mb-0">Check both boxes on an interface to run Hotspot and PPPoE together on the same port.</p>
        </div>

        <div class="d-flex align-items-center justify-content-between p-4 bg-blue-lt rounded">
            <div>
                <p class="text-uppercase small fw-bold mb-1">Before you continue</p>
                <p class="text-muted small mb-0">This configures the live hardware and finalizes the deployment.</p>
            </div>

            <button type="submit" class="btn btn-primary flex-shrink-0">
                <i class="ti ti-lock icon"></i> Lock Config &amp; Deploy
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-rp-port-row]').forEach(function (row) {
                var toggles = row.querySelectorAll('[data-rp-port-toggle]');
                var badge = row.querySelector('[data-rp-port-dynamic]');

                function sync() {
                    var anyChecked = Array.prototype.some.call(toggles, function (t) { return t.checked; });
                    badge.style.display = anyChecked ? 'none' : '';
                }

                toggles.forEach(function (t) { t.addEventListener('change', sync); });
            });
        });
    </script>
</x-sidebar-layout>
