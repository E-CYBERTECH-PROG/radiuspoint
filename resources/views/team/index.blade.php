<x-sidebar-layout title="Team">
    <div class="mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-end gap-3">
        <div>
            <h1 class="mb-1">Team</h1>
            <p class="text-muted mb-0">Staff accounts with access to your RadiusPoint dashboard.</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#rp-add-member">
            <i class="ti ti-user-plus icon"></i> Add Team Member
        </button>
    </div>

    <form method="GET" class="mb-4 d-flex flex-column flex-sm-row gap-2">
        <div class="input-icon flex-fill">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email..." class="form-control">
        </div>
        <select name="role" class="form-select w-auto">
            <option value="">All Roles</option>
            @foreach(['Admin', 'Technician', 'Sales Agent'] as $role)
                <option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>
            @endforeach
        </select>
        <x-per-page-select />
        <button type="submit" class="btn btn-dark">Filter</button>
        @if(request()->hasAny(['search', 'role']))
            <a href="{{ route('team.index') }}" class="btn btn-link align-self-center">Clear</a>
        @endif
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Added</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                        <tr>
                            <td class="fw-bold">{{ $member->name }}</td>
                            <td class="text-muted">{{ $member->email }}</td>
                            <td><span class="badge bg-primary-lt">{{ $member->role }}</span></td>
                            <td class="text-muted">{{ $member->created_at->format('d M Y') }}</td>
                            <td class="text-end">
                                @if($member->role !== 'SuperAdmin')
                                    <div class="d-flex align-items-center justify-content-end gap-3">
                                        <div class="dropdown">
                                            <button type="button" class="text-muted" style="background:none;border:0" data-bs-toggle="dropdown" data-bs-auto-close="outside" title="Edit"><i class="ti ti-edit"></i></button>
                                            <div class="dropdown-menu dropdown-menu-end p-3" style="width:18rem">
                                                <form action="{{ route('team.update', $member) }}" method="POST">
                                                    @csrf @method('PUT')
                                                    <div class="mb-2">
                                                        <label class="form-label small">Name</label>
                                                        <input type="text" name="name" required value="{{ $member->name }}" class="form-control form-control-sm">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small">Email</label>
                                                        <input type="email" name="email" required value="{{ $member->email }}" class="form-control form-control-sm">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label small">Role</label>
                                                        <select name="role" required class="form-select form-select-sm">
                                                            @foreach(['Admin', 'Technician', 'Sales Agent'] as $role)
                                                                <option value="{{ $role }}" @selected($member->role === $role)>{{ $role }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary btn-sm w-100">Save Changes</button>
                                                </form>
                                            </div>
                                        </div>
                                        <form action="{{ route('team.reset-password', $member) }}" method="POST" onsubmit="return rpConfirm(event, 'Reset password for {{ $member->name }}? A new temporary password will be generated.')">
                                            @csrf
                                            <button type="submit" class="text-muted" style="background:none;border:0" title="Reset Password"><i class="ti ti-key"></i></button>
                                        </form>
                                        @if($member->id !== Auth::id())
                                            <form action="{{ route('team.destroy', $member) }}" method="POST" onsubmit="return rpConfirm(event, 'Remove this team member?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-danger" style="background:none;border:0" title="Remove"><i class="ti ti-trash"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-users icon icon-lg mb-2 d-block"></i>
                                <p class="text-uppercase small mb-0">No team members yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $members->links() }}</div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="rp-add-member" @if($errors->any()) data-rp-autoshow @endif>
        <div class="offcanvas-header border-bottom">
            <h3 class="offcanvas-title">Add Team Member</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <form action="{{ route('team.store') }}" method="POST" class="d-flex flex-column h-100">
            @csrf
            <div class="offcanvas-body">
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" required value="{{ old('name') }}" class="form-control">
                    @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" required value="{{ old('email') }}" class="form-control">
                    @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role" required class="form-select">
                        <option value="Admin">Admin</option>
                        <option value="Technician">Technician</option>
                        <option value="Sales Agent">Sales Agent</option>
                    </select>
                </div>
                <p class="text-muted small">A random password will be generated and shown once after submitting — copy it immediately.</p>
            </div>
            <div class="offcanvas-footer p-3 border-top">
                <button type="submit" class="btn btn-primary w-100">Add Member</button>
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
