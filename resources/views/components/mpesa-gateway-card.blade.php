@props(['setting', 'label', 'icon', 'color'])

@php
    $iconClasses = [
        'blue' => 'bg-blue-lt',
        'amber' => 'bg-yellow-lt',
    ][$color] ?? 'bg-secondary-lt';

    $gatewayLabels = ['till' => 'Till (Buy Goods)', 'paybill' => 'Paybill', 'bank' => 'Bank'];
@endphp

<div {{ $attributes->merge(['class' => 'card card-sm w-100 text-start']) }}>
    <div class="card-body d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3 min-w-0">
            <span class="avatar {{ $iconClasses }}">
                <i class="ti {{ $icon }} fs-3"></i>
            </span>
            <div class="min-w-0">
                <p class="fw-bold text-body text-truncate mb-0">
                    {{ $label }} &middot; {{ $gatewayLabels[$setting->gateway_type] ?? ucfirst($setting->gateway_type) }}
                </p>
                <p class="text-muted small font-monospace mb-0">
                    @if($setting->gateway_type === 'bank')
                        {{ $setting->bank_paybill ? "Paybill {$setting->bank_paybill} · Acc {$setting->bank_account_number}" : 'No bank account set' }}
                    @else
                        {{ $setting->shortcode ?: 'No shortcode set' }}
                    @endif
                    &middot; {{ ucfirst($setting->environment) }}
                </p>
                <p class="text-muted mb-0" style="font-size:.7rem">
                    {{ filled($setting->consumer_key) && filled($setting->consumer_secret) && filled($setting->passkey) && filled($setting->shortcode) ? 'Uses its own Daraja app' : "Uses RadiusPoint's shared gateway" }}
                </p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3 shrink-0">
            <x-status-badge :color="$setting->is_active ? 'green' : 'gray'" :dot="true" :pulse="$setting->is_active">
                {{ $setting->is_active ? 'Active' : 'Inactive' }}
            </x-status-badge>
            <span class="fw-bold text-primary">Edit</span>
        </div>
    </div>
</div>
