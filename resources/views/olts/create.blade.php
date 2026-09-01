<x-sidebar-layout title="Add OLT">
    <div class="mb-4">
        <a href="{{ route('olts.index') }}" class="d-inline-flex align-items-center gap-2 mb-2">
            <i class="ti ti-arrow-left icon"></i> Back to OLT Devices
        </a>
        <h1 class="mb-1">Add OLT</h1>
        <p class="text-muted mb-0">Connects over SSH.</p>
    </div>

    <form action="{{ route('olts.store') }}" method="POST" class="card">
        @csrf

        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" required value="{{ old('name') }}" placeholder="e.g. Estate-A OLT" class="form-control">
                    @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Brand <span class="text-danger">*</span></label>
                    <select name="brand" required class="form-select">
                        <option value="vsol" @selected(old('brand') === 'vsol')>VSOL</option>
                        <option value="hioso" @selected(old('brand') === 'hioso')>Hioso</option>
                        <option value="other" @selected(old('brand', 'other') === 'other')>Other</option>
                    </select>
                    @error('brand') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">IP Address <span class="text-danger">*</span></label>
                    <input type="text" name="ip_address" required value="{{ old('ip_address') }}" placeholder="10.0.0.50" class="form-control font-monospace">
                    @error('ip_address') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">SSH Port</label>
                    <input type="number" name="ssh_port" value="{{ old('ssh_port', 22) }}" class="form-control font-monospace">
                    @error('ssh_port') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" required value="{{ old('username', 'admin') }}" class="form-control font-monospace">
                    @error('username') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" required class="form-control font-monospace">
                    @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">PON Ports</label>
                    <input type="number" name="pon_ports" value="{{ old('pon_ports') }}" placeholder="e.g. 8" class="form-control">
                    @error('pon_ports') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                    @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy icon"></i> Add OLT
            </button>
        </div>
    </form>
</x-sidebar-layout>
