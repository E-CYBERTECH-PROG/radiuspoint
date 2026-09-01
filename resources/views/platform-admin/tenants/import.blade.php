<x-sidebar-layout title="Import Tenants">
    <div class="mb-4">
        <a href="{{ route('platform-admin.tenants.index') }}" class="d-inline-flex align-items-center gap-2 mb-2">
            <i class="ti ti-arrow-left icon"></i> Back to Tenants
        </a>
        <h1 class="mb-1">Import Tenants</h1>
        <p class="text-muted mb-0">From a CSV file — each new owner gets a temporary password by email.</p>
    </div>

    <div class="card" style="max-width:42rem">
        <div class="card-body">
            <a href="{{ route('platform-admin.tenants.import-template') }}" class="d-inline-flex align-items-center gap-2 fw-bold mb-4">
                <i class="ti ti-download icon"></i> Download CSV Template
            </a>

            <form action="{{ route('platform-admin.tenants.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">CSV File <span class="text-danger">*</span></label>
                    <input type="file" name="file" accept=".csv,.txt" required class="form-control">
                    @error('file') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-upload icon"></i> Import
                </button>
            </form>
        </div>
    </div>

    @if(session('importResults'))
        @php($results = session('importResults'))
        <div class="row g-3 mt-1" style="max-width:64rem">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="card-title text-success">Created ({{ count($results['created']) }})</h3>
                        <div class="d-flex flex-column gap-1">
                            @forelse($results['created'] as $row)
                                <p class="mb-0">Row {{ $row['row'] }}: <span class="fw-bold">{{ $row['company_name'] }}</span> &middot; {{ $row['owner_email'] }}</p>
                            @empty
                                <p class="text-muted mb-0">None.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="card-title text-danger">Failed ({{ count($results['failed']) }})</h3>
                        <div class="d-flex flex-column gap-1">
                            @forelse($results['failed'] as $row)
                                <p class="mb-0">Row {{ $row['row'] }}: <span class="text-danger">{{ $row['reason'] }}</span></p>
                            @empty
                                <p class="text-muted mb-0">None.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-sidebar-layout>
