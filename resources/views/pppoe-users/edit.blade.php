<x-sidebar-layout title="Edit PPPoE Customer">
    <div class="mb-4">
        <a href="{{ route('pppoe-users.index') }}" class="d-inline-flex align-items-center gap-2 mb-2">
            <i class="ti ti-arrow-left icon"></i> Back to PPPoE Users
        </a>
        <h1 class="mb-1">Edit PPPoE Customer</h1>
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
                        {{ $pppoe_user->expires_at?->format('d M Y H:i') ?? '—' }}
                    </p>
                    @if($pppoe_user->expires_at)
                        <p class="small mt-1 mb-0 {{ $pppoe_user->expires_at->isPast() ? 'text-danger' : 'text-muted' }}">
                            {{ $pppoe_user->expires_at->diffForHumans() }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="row g-3 pt-3 border-top">
                <div class="col-md-6">
                    <p class="text-uppercase text-muted small fw-bold mb-2">Extend</p>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @foreach([1 => '+1 Day', 7 => '+7 Days', 30 => '+30 Days'] as $days => $label)
                            <form action="{{ route('pppoe-users.extend', $pppoe_user) }}" method="POST">
                                @csrf
                                <input type="hidden" name="days" value="{{ $days }}">
                                <button type="submit" class="btn btn-sm">{{ $label }}</button>
                            </form>
                        @endforeach
                        <form action="{{ route('pppoe-users.extend', $pppoe_user) }}" method="POST" class="d-flex align-items-center gap-2">
                            @csrf
                            <input type="datetime-local" name="expires_at" class="form-control form-control-sm">
                            <button type="submit" class="btn btn-sm">Set Date</button>
                        </form>
                    </div>
                </div>

                <div class="col-md-6">
                    <p class="text-uppercase text-muted small fw-bold mb-2">Session</p>
                    <form action="{{ route('pppoe-users.disconnect', $pppoe_user) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm"><i class="ti ti-power"></i> Force Disconnect</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('pppoe-users.update', $pppoe_user) }}" method="POST" class="card">
        @csrf
        @method('PUT')

        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" required value="{{ old('username', $pppoe_user->username) }}" class="form-control">
                    @error('username') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $pppoe_user->name) }}" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone_number" value="{{ old('phone_number', $pppoe_user->phone_number) }}" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Package</label>
                    <select name="current_plan_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" @selected($pppoe_user->current_plan_id == $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Router</label>
                    <select name="current_router_id" class="form-select">
                        <option value="">— None —</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}" @selected($pppoe_user->current_router_id == $router->id)>{{ $router->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" required class="form-select">
                        <option value="active" @selected($pppoe_user->status == 'active')>Active</option>
                        <option value="expired" @selected($pppoe_user->status == 'expired')>Expired</option>
                        <option value="offline" @selected($pppoe_user->status == 'offline')>Offline</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Expires At</label>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $pppoe_user->expires_at?->format('Y-m-d\TH:i')) }}" class="form-control">
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
