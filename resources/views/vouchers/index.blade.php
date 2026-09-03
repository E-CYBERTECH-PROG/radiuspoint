{{-- Expects $plans (hotspot plans, for the offcanvas), $vouchers (paginated HotspotUser list,
     current $tab), $tab ('available'|'online'|'expired'), $counts (status => count),
     $plansById (all plans keyed by id, for the Package column) in scope. --}}
<x-sidebar-layout title="Vouchers">

    {{-- === TABS === --}}
    <ul class="nav nav-pills mb-3">
        @foreach(['available' => 'Available', 'online' => 'Online', 'expired' => 'Expired'] as $key => $label)
            <li class="nav-item">
                <a href="{{ route('vouchers.index', array_filter(['tab' => $key, 'search' => request('search')])) }}" class="nav-link {{ $tab === $key ? 'active' : '' }}">
                    {{ $label }} ({{ $counts[\App\Http\Controllers\VoucherController::STATUS_MAP[$key]] ?? 0 }})
                </a>
            </li>
        @endforeach
    </ul>

    {{-- === NEW VOUCHER OFFCANVAS === --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="rp-add-voucher" @if($errors->any() || request('add')) data-rp-autoshow @endif>
        <div class="offcanvas-header border-bottom">
            <h3 class="offcanvas-title">New Voucher</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <form action="{{ route('vouchers.generate') }}" method="POST" class="d-flex flex-column h-100">
            @csrf
            <div class="offcanvas-body">
                <div class="mb-3">
                    <label class="form-label">Package <span class="text-danger">*</span></label>
                    <select name="plan_id" required class="form-select">
                        @forelse($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} — KES {{ number_format($plan->price) }}</option>
                        @empty
                            <option value="" disabled>No hotspot packages yet — create one first</option>
                        @endforelse
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mode <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-selectgroup-item w-100">
                                <input type="radio" name="mode" value="single" class="form-selectgroup-input" id="rp-voucher-mode-single" checked>
                                <span class="form-selectgroup-label text-center">Single</span>
                            </label>
                        </div>
                        <div class="col-6">
                            <label class="form-selectgroup-item w-100">
                                <input type="radio" name="mode" value="batch" class="form-selectgroup-input" id="rp-voucher-mode-batch">
                                <span class="form-selectgroup-label text-center">Batch</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div data-rp-mode="single" class="mb-3">
                    <label class="form-label">Voucher Code <span class="text-muted text-lowercase">(optional — auto-generated if blank)</span></label>
                    <input type="text" name="username" placeholder="Leave blank to auto-generate" class="form-control">
                </div>

                <div data-rp-mode="single" class="mb-3">
                    <label class="form-label">Send To Phone <span class="text-muted text-lowercase">(optional)</span></label>
                    <input type="tel" name="phone" placeholder="0712345678" class="form-control">
                </div>

                <div data-rp-mode="batch" class="mb-3" style="display:none">
                    <label class="form-label">Quantity <span class="text-danger">*</span> <span class="text-muted text-lowercase">(max 21 per print page)</span></label>
                    <input type="number" name="quantity" min="1" max="21" value="21" class="form-control">
                </div>
            </div>

            <div class="offcanvas-footer p-3 border-top">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-ticket icon"></i> Generate
                </button>
            </div>
        </form>
    </div>

    {{-- === TABLE CARD === --}}
    <form method="GET">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="card">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Show</span>
                <x-per-page-select :default="10" />
                <span class="text-muted small">Entries</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <div class="input-icon">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Voucher code…" class="form-control">
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#rp-add-voucher">
                    <i class="ti ti-plus icon"></i> <span class="d-none d-sm-inline">New Voucher</span>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Package</th>
                        <th>Status</th>
                        <th>Expiry</th>
                        <th>Created On</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $voucher)
                        <tr>
                            <td class="font-monospace fw-bold">{{ $voucher->phone_number }}</td>
                            <td class="text-muted">{{ $plansById[$voucher->current_plan_id]->name ?? '—' }}</td>
                            <td>
                                @if($voucher->status === 'active')
                                    <x-status-badge color="green">online</x-status-badge>
                                @elseif($voucher->status === 'expired')
                                    <x-status-badge color="red">expired</x-status-badge>
                                @else
                                    <x-status-badge color="gray">available</x-status-badge>
                                @endif
                            </td>
                            <td class="text-muted">{{ $voucher->expires_at?->format('H:i M d, Y') ?? '—' }}</td>
                            <td class="text-muted">{{ $voucher->created_at->format('H:i M d, Y') }}</td>
                            <td class="text-end">
                                <form action="{{ route('vouchers.destroy', $voucher) }}" method="POST" onsubmit="return rpConfirm(event, 'Remove this voucher? This also removes its RADIUS credential.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-danger" style="background:none;border:0" title="Delete"><i class="ti ti-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-ticket fs-1"></i></span>
                                <p class="text-uppercase text-muted small mb-0">No {{ $tab }} vouchers.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">{{ $vouchers->links('vendor.pagination.rp-circles') }}</div>
    </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-rp-autoshow]').forEach(function (el) {
                bootstrap.Offcanvas.getOrCreateInstance(el).show();
            });

            function syncMode() {
                var mode = document.getElementById('rp-voucher-mode-batch').checked ? 'batch' : 'single';
                document.querySelectorAll('[data-rp-mode]').forEach(function (el) {
                    el.style.display = el.getAttribute('data-rp-mode') === mode ? '' : 'none';
                });
            }

            document.getElementById('rp-voucher-mode-single').addEventListener('change', syncMode);
            document.getElementById('rp-voucher-mode-batch').addEventListener('change', syncMode);
        });
    </script>
</x-sidebar-layout>
