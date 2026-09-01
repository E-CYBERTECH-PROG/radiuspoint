<x-sidebar-layout title="Edit {{ $tenant->company_name }}">
    <div class="mb-4">
        <a href="{{ route('platform-admin.tenants.show', $tenant) }}" class="d-inline-flex align-items-center gap-2 mb-2">
            <i class="ti ti-arrow-left icon"></i> Back to {{ $tenant->company_name }}
        </a>
        <h1 class="mb-1">Edit Tenant</h1>
        <p class="text-muted mb-0">Update company details and subscription.</p>
    </div>

    <form action="{{ route('platform-admin.tenants.update', $tenant) }}" method="POST" class="card">
        @csrf
        @method('PUT')

        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" required value="{{ old('company_name', $tenant->company_name) }}" class="form-control">
                    @error('company_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Subdomain</label>
                    <input type="text" name="subdomain" value="{{ old('subdomain', $tenant->subdomain) }}" class="form-control">
                    @error('subdomain') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Subscription Tier <span class="text-danger">*</span></label>
                    <select name="subscription_tier" required class="form-select">
                        @foreach(['free', 'starter', 'pro'] as $tier)
                            <option value="{{ $tier }}" @selected(old('subscription_tier', $tenant->subscription_tier) === $tier)>{{ ucfirst($tier) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Subscription Status <span class="text-danger">*</span></label>
                    <select name="subscription_status" required class="form-select">
                        @foreach(['trial', 'active', 'expired', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected(old('subscription_status', $tenant->subscription_status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Subscription Expires At</label>
                    <input type="date" name="subscription_expires_at" value="{{ old('subscription_expires_at', optional($tenant->subscription_expires_at)->format('Y-m-d')) }}" class="form-control">
                </div>

                <div class="col-12">
                    <label class="form-label">Admin Notes</label>
                    <textarea name="admin_notes" rows="4" class="form-control">{{ old('admin_notes', $tenant->admin_notes) }}</textarea>
                </div>
            </div>
        </div>

        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy icon"></i> Save Changes
            </button>
        </div>
    </form>
</x-sidebar-layout>
