<x-sidebar-layout title="Manual Recharges">
    <div class="mb-3">
        <h2 class="mb-0">Manual Recharges</h2>
        <p class="text-muted small mb-0">Every wallet adjustment and expiry extension made by staff.</p>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="card card-sm"><div class="card-body">
                <div class="text-muted small text-uppercase">Credited</div>
                <div class="h3 mb-0 text-success">{{ number_format($totals->credited ?? 0, 2) }}</div>
            </div></div>
        </div>
        <div class="col-4">
            <div class="card card-sm"><div class="card-body">
                <div class="text-muted small text-uppercase">Debited</div>
                <div class="h3 mb-0 text-danger">{{ number_format($totals->debited ?? 0, 2) }}</div>
            </div></div>
        </div>
        <div class="col-4">
            <div class="card card-sm"><div class="card-body">
                <div class="text-muted small text-uppercase">Extensions</div>
                <div class="h3 mb-0">{{ number_format($totals->extensions ?? 0) }}</div>
            </div></div>
        </div>
    </div>

    <form method="GET">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 bg-body-secondary border-bottom py-3">
                <div class="btn-group" role="group">
                    @foreach(['' => 'All', 'balance' => 'Wallet', 'extension' => 'Extensions'] as $value => $label)
                        <a href="{{ request()->fullUrlWithQuery(['kind' => $value ?: null, 'page' => null]) }}"
                           class="btn btn-sm {{ (string) request('kind') === $value ? 'btn-primary' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Phone or username…" class="form-control">
                    </div>
                    <button type="button" class="btn btn-icon" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-filters-manual-recharges" title="Date range">
                        <i class="ti ti-calendar icon"></i>
                    </button>
                </div>
                @if(request('kind'))<input type="hidden" name="kind" value="{{ request('kind') }}">@endif
            </div>

            <div class="table-responsive">
                <table class="table card-table table-vcenter table-sm table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Change</th>
                            <th class="d-none d-md-table-cell">Reason</th>
                            <th class="d-none d-sm-table-cell">By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                            @php
                                $customer = $entry->hotspotUser ?? $entry->pppoeUser;
                                $type = $entry->hotspotUser ? 'hotspot' : 'pppoe';
                                $label = $entry->hotspotUser
                                    ? ($entry->hotspotUser->phone_number ?: $entry->hotspotUser->username)
                                    : ($entry->pppoeUser?->username);
                            @endphp
                            <tr>
                                <td class="text-muted small">{{ $entry->created_at->timezone(config('app.timezone'))->format('d M Y, H:i') }}</td>
                                <td>
                                    @if($customer)
                                        <a href="{{ route('customers.show', ['type' => $type, 'token' => \App\Http\Controllers\CustomerController::tokenFor($type, $customer->id)]) }}" class="fw-bold font-monospace text-body">{{ $label }}</a>
                                        <span class="badge bg-{{ $type === 'hotspot' ? 'orange' : 'blue' }}-lt ms-1">{{ $type === 'hotspot' ? 'Hotspot' : 'PPPoE' }}</span>
                                    @else
                                        <span class="text-muted">Deleted customer</span>
                                    @endif
                                </td>
                                <td>
                                    @if($entry->kind === 'extension')
                                        <x-status-badge color="blue" dot>Extension</x-status-badge>
                                    @elseif($entry->amount >= 0)
                                        <x-status-badge color="green" dot>Credit</x-status-badge>
                                    @else
                                        <x-status-badge color="red" dot>Debit</x-status-badge>
                                    @endif
                                </td>
                                <td class="small">
                                    @if($entry->kind === 'extension')
                                        <span class="text-muted">{{ $entry->previous_expires_at?->timezone(config('app.timezone'))->format('d M H:i') ?? 'none' }}</span>
                                        <i class="ti ti-arrow-right mx-1 text-muted"></i>
                                        <span class="fw-bold">{{ $entry->new_expires_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</span>
                                    @else
                                        <span class="fw-bold {{ $entry->amount >= 0 ? 'text-success' : 'text-danger' }}">{{ $entry->amount >= 0 ? '+' : '−' }}{{ number_format(abs($entry->amount), 2) }}</span>
                                    @endif
                                </td>
                                <td class="d-none d-md-table-cell text-muted small text-truncate" style="max-width:16rem">{{ $entry->reason }}</td>
                                <td class="d-none d-sm-table-cell text-muted small">{{ $entry->createdBy->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-receipt-refund fs-1"></i></span>
                                    <p class="text-uppercase text-muted small mb-0">No manual recharges recorded yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-filter-modal name="manual-recharges" :clear-url="route('reports.manual-recharges')">
            <div class="col-12 col-sm-6">
                <label class="form-label">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control">
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control">
            </div>
        </x-filter-modal>
    </form>

    <div class="mt-3">{{ $entries->links('vendor.pagination.rp-circles') }}</div>
</x-sidebar-layout>
