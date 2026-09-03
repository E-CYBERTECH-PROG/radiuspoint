<x-sidebar-layout title="Leads">
    <div class="mb-4">
        <h1 class="mb-1">Leads</h1>
        <p class="text-muted mb-0">Prospective customers captured from field agents and campaigns.</p>
    </div>

    <form method="GET">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Show</span>
                    <x-per-page-select />
                    <span class="text-muted small">Entries</span>
                    <button type="button" class="btn btn-icon" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-filters-leads" title="Filters">
                        <i class="ti ti-filter icon"></i>
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or phone…" class="form-control">
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#rp-add-lead">
                        <i class="ti ti-plus icon"></i> <span class="d-none d-sm-inline">New Lead</span>
                    </button>
                </div>
            </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Notes</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                        <tr>
                            <td class="fw-bold">{{ $lead->name }}</td>
                            <td class="text-muted">{{ $lead->phone }}</td>
                            <td class="text-muted">{{ $lead->location ?: '—' }}</td>
                            <td class="text-muted text-truncate" style="max-width:16rem">{{ $lead->notes ?: '—' }}</td>
                            <td class="text-muted">{{ $lead->created_at->format('d M Y') }}</td>
                            <td>
                                <form action="{{ route('leads.update-status', $lead) }}" method="POST" class="d-inline-block">
                                    @csrf @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" class="form-select form-select-sm {{ $lead->status == 'converted' ? 'text-success' : ($lead->status == 'lost' ? 'text-danger' : 'text-warning') }}">
                                        <option value="new" @selected($lead->status == 'new')>New</option>
                                        <option value="contacted" @selected($lead->status == 'contacted')>Contacted</option>
                                        <option value="converted" @selected($lead->status == 'converted')>Converted</option>
                                        <option value="lost" @selected($lead->status == 'lost')>Lost</option>
                                    </select>
                                </form>
                            </td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-3">
                                    @if($lead->status !== 'converted')
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-link btn-sm p-0 text-uppercase" data-bs-toggle="dropdown" data-bs-auto-close="outside">Convert</button>
                                            <div class="dropdown-menu dropdown-menu-end p-3" style="width:16rem">
                                                <form action="{{ route('leads.convert', $lead) }}" method="POST">
                                                    @csrf
                                                    <label class="form-label small">Convert to</label>
                                                    <select name="type" class="form-select form-select-sm mb-2">
                                                        <option value="pppoe">PPPoE Customer</option>
                                                        <option value="hotspot">Hotspot Customer</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-primary btn-sm w-100">Confirm</button>
                                                </form>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted text-uppercase small">Converted</span>
                                    @endif
                                    <div class="dropdown">
                                        <button type="button" class="text-muted" style="background:none;border:0" data-bs-toggle="dropdown" data-bs-auto-close="outside" title="Edit"><i class="ti ti-edit"></i></button>
                                        <div class="dropdown-menu dropdown-menu-end p-3" style="width:18rem">
                                            <form action="{{ route('leads.update', $lead) }}" method="POST">
                                                @csrf @method('PUT')
                                                <div class="mb-2">
                                                    <label class="form-label small">Name</label>
                                                    <input type="text" name="name" required value="{{ $lead->name }}" class="form-control form-control-sm">
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small">Phone</label>
                                                    <input type="tel" name="phone" required value="{{ $lead->phone }}" class="form-control form-control-sm">
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small">Location</label>
                                                    <input type="text" name="location" value="{{ $lead->location }}" class="form-control form-control-sm">
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small">Notes</label>
                                                    <textarea name="notes" maxlength="200" rows="3" class="form-control form-control-sm">{{ $lead->notes }}</textarea>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm w-100">Save Changes</button>
                                            </form>
                                        </div>
                                    </div>
                                    <form action="{{ route('leads.destroy', $lead) }}" method="POST" onsubmit="return rpConfirm(event, 'Remove this lead?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-danger" style="background:none;border:0" title="Remove"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-user-plus fs-1"></i></span>
                                <p class="text-uppercase text-muted small mb-0">No leads captured yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>

        <x-filter-modal name="leads" :clear-url="route('leads.index')">
            <div class="col-12">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['new', 'contacted', 'converted', 'lost'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
        </x-filter-modal>
    </form>

    <div class="mt-3">{{ $leads->links('vendor.pagination.rp-circles') }}</div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="rp-add-lead" @if($errors->any()) data-rp-autoshow @endif>
        <div class="offcanvas-header border-bottom">
            <h3 class="offcanvas-title">New Lead</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <form action="{{ route('leads.store') }}" method="POST" class="d-flex flex-column h-100">
            @csrf
            <div class="offcanvas-body">
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" required value="{{ old('name') }}" placeholder="Jane Doe" class="form-control">
                    @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" required value="{{ old('phone') }}" placeholder="0712345678" class="form-control">
                    @error('phone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Location <span class="text-muted text-lowercase">(optional)</span></label>
                    <input type="text" name="location" value="{{ old('location') }}" placeholder="Cherunya" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes <span class="text-muted text-lowercase">(optional, max 200 chars)</span> <span id="rp-lead-notes-count">{{ strlen(old('notes', '')) }}</span>/200</label>
                    <textarea name="notes" maxlength="200" id="rp-lead-notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="offcanvas-footer p-3 border-top">
                <button type="submit" class="btn btn-primary w-100">Save Lead</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-rp-autoshow]').forEach(function (el) {
                bootstrap.Offcanvas.getOrCreateInstance(el).show();
            });

            var notes = document.getElementById('rp-lead-notes');
            var count = document.getElementById('rp-lead-notes-count');
            if (notes && count) {
                notes.addEventListener('input', function () { count.textContent = notes.value.length; });
            }
        });
    </script>
</x-sidebar-layout>
