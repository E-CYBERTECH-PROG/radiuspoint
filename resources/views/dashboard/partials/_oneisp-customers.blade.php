{{-- Expects $stats in scope, plus an optional $delay (ms). --}}
@php
    $oneispCustomerTiles = [
        ['label' => 'Total', 'value' => $stats['customers_total'] ?? 0, 'icon' => 'ti-user', 'bg' => 'bg-primary-lt'],
        ['label' => 'Active', 'value' => ($stats['hotspot_active'] ?? 0) + ($stats['pppoe_active'] ?? 0), 'icon' => 'ti-user-check', 'bg' => 'bg-azure-lt'],
        ['label' => 'Expired', 'value' => $stats['customers_expired'] ?? 0, 'icon' => 'ti-user-x', 'bg' => 'bg-red-lt'],
        ['label' => 'Online', 'value' => $stats['online_now'] ?? 0, 'icon' => 'ti-wifi', 'bg' => 'bg-green-lt'],
        ['label' => 'Online PPP', 'value' => $stats['online_ppp'] ?? 0, 'icon' => 'ti-device-desktop', 'bg' => 'bg-green-lt'],
        ['label' => 'Online Hotspot', 'value' => $stats['online_hotspot'] ?? 0, 'icon' => 'ti-broadcast', 'bg' => 'bg-teal-lt'],
    ];
@endphp
<div class="card h-100 rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="card-body">
        <h3 class="card-title">Customers</h3>
        <div class="row g-3">
            @foreach($oneispCustomerTiles as $tile)
                <div class="col-6 col-sm-4 col-lg-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-sm {{ $tile['bg'] }} flex-shrink-0"><i class="ti {{ $tile['icon'] }}"></i></span>
                        <div class="min-w-0">
                            <p class="font-monospace fw-bold mb-0 lh-sm">{{ number_format($tile['value']) }}</p>
                            <p class="text-muted text-truncate mb-0" style="font-size:.6875rem">{{ $tile['label'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
