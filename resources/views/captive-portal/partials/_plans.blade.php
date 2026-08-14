<div class="body">
    <p class="section-label">Choose a plan</p>

    @forelse($plans as $plan)
        <div class="plan">
            <div>
                <div class="name">{{ $plan->name }}</div>
                <div class="meta">{{ $plan->duration_value }} {{ ucfirst($plan->duration_unit) }} &middot; {{ $plan->speed_limit }}</div>
                @if($tier = $plan->speedTierLabel())
                    <div class="tier">{{ $tier }}</div>
                @endif
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <span class="price">KES {{ number_format($plan->price) }}</span>
                <button type="button" class="btn btn-brand" onclick="rpPortal.openBuy({{ $plan->id }}, {{ Illuminate\Support\Js::from($plan->name) }})">Buy</button>
            </div>
        </div>
    @empty
        <p class="empty">No packages are available on this network yet.</p>
    @endforelse

    <div class="divider"></div>
    <div class="actions-row">
        <button type="button" class="action-card" onclick="rpPortal.openReconnect()">
            <span class="action-card-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M3 12a9 9 0 1 1 2.64 6.36" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 17v-5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span class="action-card-label">Already Paid?</span>
        </button>
        <button type="button" class="action-card" onclick="rpPortal.openVoucher()">
            <span class="action-card-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1.5a1.5 1.5 0 0 0 0 3V15a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1.5a1.5 1.5 0 0 0 0-3V9Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 7v10" stroke="currentColor" stroke-width="1.8" stroke-dasharray="2 2"/></svg></span>
            <span class="action-card-label">Have a Voucher?</span>
        </button>
        <button type="button" class="action-card" onclick="rpPortal.openReceipt()">
            <span class="action-card-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 8h6M9 12h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
            <span class="action-card-label">Paste M-Pesa Message</span>
        </button>
    </div>

    <button type="button" class="btn btn-block" style="background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; margin-top:10px;" onclick="rpPortal.openFreeMode()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 2 4 6v6c0 5 3.5 8.7 8 10 4.5-1.3 8-5 8-10V6l-8-4Z" stroke="#15803d" stroke-width="1.5" stroke-linejoin="round"/></svg>
        No money? Use Free Mode
    </button>
</div>
