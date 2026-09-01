<x-sidebar-layout title="Support Tickets">
    <div class="mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3">
        <div>
            <h1 class="mb-1">Support Tickets</h1>
            <p class="text-muted mb-0">Customer issues and support requests.</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#rp-add-ticket">
            <i class="ti ti-plus icon"></i> Add Ticket
        </button>
    </div>

    <form method="GET" class="mb-4 d-flex flex-column flex-sm-row gap-2">
        <div class="input-icon flex-fill">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search username or phone..." class="form-control">
        </div>
        <select name="status" class="form-select w-auto">
            <option value="">All Statuses</option>
            @foreach(['open', 'in_progress', 'resolved', 'closed'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
        <x-per-page-select />
        <button type="submit" class="btn btn-dark">Filter</button>
        @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('tickets.index') }}" class="btn btn-link align-self-center">Clear</a>
        @endif
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Username / Phone</th>
                        <th>Notes</th>
                        <th>Date</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $ticket->username }}</div>
                                @if($ticket->phone)
                                    <div class="text-muted" style="font-size:.7rem">{{ $ticket->phone }}</div>
                                @endif
                            </td>
                            <td class="text-muted text-wrap" style="max-width:24rem">{{ $ticket->notes }}</td>
                            <td class="text-muted">{{ $ticket->created_at->format('d M Y H:i') }}</td>
                            <td class="text-center">
                                <form action="{{ route('tickets.update-status', $ticket) }}" method="POST" class="d-inline-block">
                                    @csrf @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" class="form-select form-select-sm {{ $ticket->status == 'resolved' || $ticket->status == 'closed' ? 'text-success' : ($ticket->status == 'in_progress' ? 'text-primary' : 'text-warning') }}">
                                        <option value="open" @selected($ticket->status == 'open')>Open</option>
                                        <option value="in_progress" @selected($ticket->status == 'in_progress')>In Progress</option>
                                        <option value="resolved" @selected($ticket->status == 'resolved')>Resolved</option>
                                        <option value="closed" @selected($ticket->status == 'closed')>Closed</option>
                                    </select>
                                </form>
                            </td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-3">
                                    <div class="dropdown">
                                        <button type="button" class="text-muted" style="background:none;border:0" data-bs-toggle="dropdown" data-bs-auto-close="outside" title="Edit"><i class="ti ti-edit"></i></button>
                                        <div class="dropdown-menu dropdown-menu-end p-3" style="width:18rem">
                                            <form action="{{ route('tickets.update', $ticket) }}" method="POST">
                                                @csrf @method('PUT')
                                                <div class="mb-2">
                                                    <label class="form-label small">Username / Phone</label>
                                                    <input type="text" name="username" required value="{{ $ticket->username }}" class="form-control form-control-sm">
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small">Phone</label>
                                                    <input type="tel" name="phone" value="{{ $ticket->phone }}" class="form-control form-control-sm">
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small">Notes</label>
                                                    <textarea name="notes" required rows="3" class="form-control form-control-sm">{{ $ticket->notes }}</textarea>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm w-100">Save Changes</button>
                                            </form>
                                        </div>
                                    </div>
                                    <form action="{{ route('tickets.destroy', $ticket) }}" method="POST" onsubmit="return rpConfirm(event, 'Remove this ticket?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-danger" style="background:none;border:0" title="Remove"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-headset icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-0">No support tickets yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $tickets->links() }}</div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="rp-add-ticket" @if($errors->any()) data-rp-autoshow @endif>
        <div class="offcanvas-header border-bottom">
            <h3 class="offcanvas-title">Add Ticket</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <form action="{{ route('tickets.store') }}" method="POST" class="d-flex flex-column h-100">
            @csrf
            <div class="offcanvas-body">
                <div class="mb-3">
                    <label class="form-label">Username / Phone <span class="text-danger">*</span></label>
                    <input type="text" name="username" required value="{{ old('username') }}" placeholder="username or 0712345678" class="form-control">
                    @error('username') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone <span class="text-muted text-lowercase">(optional)</span></label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="0712345678" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes <span class="text-danger">*</span></label>
                    <textarea name="notes" required rows="4" class="form-control">{{ old('notes') }}</textarea>
                    @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="offcanvas-footer p-3 border-top">
                <button type="submit" class="btn btn-primary w-100">Submit Ticket</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-rp-autoshow]').forEach(function (el) {
                bootstrap.Offcanvas.getOrCreateInstance(el).show();
            });
        });
    </script>
</x-sidebar-layout>
