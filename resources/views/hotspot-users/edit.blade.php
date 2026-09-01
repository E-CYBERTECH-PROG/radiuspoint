<x-sidebar-layout title="Edit Hotspot Customer">
    <div class="mb-4">
        <a href="{{ route('hotspot-users.index') }}" class="d-inline-flex align-items-center gap-2 mb-2">
            <i class="ti ti-arrow-left icon"></i> Back to Hotspot Users
        </a>
        <h1 class="mb-1">Edit Hotspot Customer</h1>
        <p class="text-muted mb-0">Update this customer's details.</p>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="card-title">Quick Actions</h2>

            <div class="row g-4 mb-3">
                <div class="col-md-6">
                    <p class="text-uppercase text-muted small fw-bold mb-2">Data Usage This Cycle</p>
                    @if($usage)
                        <p class="fs-4 font-monospace fw-bold mb-0">
                            {{ number_format($usage['used_mb']) }} MB{{ $usage['cap_mb'] ? ' / ' . number_format($usage['cap_mb']) . ' MB' : '' }}
                            @if($usage['throttled'])
                                <span class="badge bg-red-lt text-uppercase align-middle ms-1">Throttled</span>
                            @endif
                        </p>
                        @if($usage['percent'] !== null)
                            <div class="progress progress-sm mt-2">
                                <div class="progress-bar {{ $usage['percent'] >= 100 ? 'bg-red' : ($usage['percent'] >= 80 ? 'bg-amber' : 'bg-blue') }}" style="width: {{ $usage['percent'] }}%"></div>
                            </div>
                        @endif
                        <p class="text-muted small mt-1 mb-0">Since {{ $usage['cycle_start']->format('d M Y H:i') }}</p>
                    @else
                        <p class="text-muted">No plan assigned yet.</p>
                    @endif
                </div>

                <div class="col-md-6">
                    <p class="text-uppercase text-muted small fw-bold mb-2">Current Expiry</p>
                    <p class="fs-4 font-monospace fw-bold mb-0">
                        {{ $hotspot_user->expires_at ? \Carbon\Carbon::parse($hotspot_user->expires_at)->format('d M Y H:i') : '—' }}
                    </p>
                    @if($hotspot_user->expires_at)
                        <p class="small mt-1 mb-0 {{ \Carbon\Carbon::parse($hotspot_user->expires_at)->isPast() ? 'text-danger' : 'text-muted' }}">
                            {{ \Carbon\Carbon::parse($hotspot_user->expires_at)->diffForHumans() }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="row g-3 pt-3 border-top">
                <div class="col-md-6">
                    <p class="text-uppercase text-muted small fw-bold mb-2">Extend</p>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @foreach([1 => '+1 Day', 7 => '+7 Days', 30 => '+30 Days'] as $days => $label)
                            <form action="{{ route('hotspot-users.extend', $hotspot_user) }}" method="POST">
                                @csrf
                                <input type="hidden" name="days" value="{{ $days }}">
                                <button type="submit" class="btn btn-sm">{{ $label }}</button>
                            </form>
                        @endforeach
                        <form action="{{ route('hotspot-users.extend', $hotspot_user) }}" method="POST" class="d-flex align-items-center gap-2">
                            @csrf
                            <input type="datetime-local" name="expires_at" class="form-control form-control-sm">
                            <button type="submit" class="btn btn-sm">Set Date</button>
                        </form>
                    </div>
                </div>

                <div class="col-md-6">
                    <p class="text-uppercase text-muted small fw-bold mb-2">Session</p>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <form action="{{ route('hotspot-users.disconnect', $hotspot_user) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm"><i class="ti ti-power"></i> Force Disconnect</button>
                        </form>
                        <form action="{{ route('hotspot-users.reset-mac', $hotspot_user) }}" method="POST" onsubmit="return rpConfirm(event, 'Clear this customer\'s bound MAC address? The next device to connect with these credentials will bind automatically.')">
                            @csrf
                            <button type="submit" class="btn btn-sm"><i class="ti ti-refresh"></i> Reset MAC</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('hotspot-users.update', $hotspot_user) }}" method="POST" class="card">
        @csrf
        @method('PUT')

        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" name="phone_number" required value="{{ old('phone_number', $hotspot_user->phone_number) }}" class="form-control">
                    @error('phone_number') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">MAC Address</label>
                    <input type="text" name="mac_address" value="{{ old('mac_address', $hotspot_user->mac_address) }}" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Package</label>
                    <select name="current_plan_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" @selected($hotspot_user->current_plan_id == $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Router</label>
                    <select name="current_router_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}" @selected($hotspot_user->current_router_id == $router->id)>{{ $router->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" required class="form-select">
                        @foreach(\App\Models\HotspotUser::STATUSES as $status)
                            <option value="{{ $status }}" @selected($hotspot_user->status == $status)>{{ $status === 'unused' ? 'Unused (Voucher)' : ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Expires At</label>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $hotspot_user->expires_at ? \Carbon\Carbon::parse($hotspot_user->expires_at)->format('Y-m-d\TH:i') : null) }}" class="form-control">
                </div>
            </div>
        </div>

        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy icon"></i> Update Customer
            </button>
        </div>
    </form>
</x-sidebar-layout>
