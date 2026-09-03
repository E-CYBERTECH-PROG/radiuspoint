{{-- Expects $expenses (paginated Expense list) and $stats (this_month/this_year/all_time sums). --}}
<x-sidebar-layout title="Expenses">
    @php $currency = Auth::user()->tenant->currency_symbol ?? 'KES'; @endphp

    <div class="mb-4">
        <h1 class="mb-1">Expenses</h1>
        <p class="text-muted mb-0">Track what it costs to run your ISP.</p>
    </div>

    <div class="card mb-4" style="border-radius:.5rem">
        <div class="d-flex flex-column flex-sm-row rp-stat-strip">
            <div class="flex-fill p-3">
                <p class="text-uppercase text-muted small fw-bold mb-1">This Month</p>
                <p class="fs-3 fw-bold font-monospace mb-0">{{ $currency }} {{ number_format($stats['this_month'], 2) }}</p>
            </div>
            <div class="flex-fill p-3">
                <p class="text-uppercase text-muted small fw-bold mb-1">This Year</p>
                <p class="fs-3 fw-bold font-monospace mb-0">{{ $currency }} {{ number_format($stats['this_year'], 2) }}</p>
            </div>
            <div class="flex-fill p-3">
                <p class="text-uppercase text-muted small fw-bold mb-1">All Time</p>
                <p class="fs-3 fw-bold font-monospace mb-0">{{ $currency }} {{ number_format($stats['all_time'], 2) }}</p>
            </div>
        </div>
    </div>

    <form method="GET">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Show</span>
                    <x-per-page-select />
                    <span class="text-muted small">Entries</span>
                    <button type="button" class="btn btn-icon" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-filters-expenses" title="Filters">
                        <i class="ti ti-filter icon"></i>
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search category or notes…" class="form-control">
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#rp-add-expense">
                        <i class="ti ti-plus icon"></i> <span class="d-none d-sm-inline">Log Expense</span>
                    </button>
                </div>
            </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Notes</th>
                        <th>Date</th>
                        <th>Logged By</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td><span class="badge bg-primary-lt">{{ $expense->category }}</span></td>
                            <td class="text-muted text-truncate" style="max-width:20rem">{{ $expense->notes ?: '—' }}</td>
                            <td class="text-muted">{{ $expense->spent_at->format('d M Y') }}</td>
                            <td class="text-muted">{{ $expense->recordedBy?->name ?? '—' }}</td>
                            <td class="text-end font-monospace fw-bold">{{ $currency }} {{ number_format($expense->amount, 2) }}</td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-3">
                                    <div class="dropdown">
                                        <button type="button" class="text-muted" style="background:none;border:0" data-bs-toggle="dropdown" data-bs-auto-close="outside" title="Edit"><i class="ti ti-edit"></i></button>
                                        <div class="dropdown-menu dropdown-menu-end p-3" style="width:18rem">
                                            <form action="{{ route('expenses.update', $expense) }}" method="POST">
                                                @csrf @method('PUT')
                                                <div class="mb-2">
                                                    <label class="form-label small">Category</label>
                                                    <select name="category" required class="form-select form-select-sm">
                                                        @foreach(\App\Http\Controllers\ExpenseController::CATEGORIES as $cat)
                                                            <option value="{{ $cat }}" @selected($expense->category === $cat)>{{ $cat }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small">Amount</label>
                                                    <input type="number" step="0.01" min="0.01" name="amount" required value="{{ $expense->amount }}" class="form-control form-control-sm">
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small">Date</label>
                                                    <input type="date" name="spent_at" required value="{{ $expense->spent_at->format('Y-m-d') }}" class="form-control form-control-sm">
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small">Notes</label>
                                                    <textarea name="notes" rows="2" class="form-control form-control-sm">{{ $expense->notes }}</textarea>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm w-100">Save Changes</button>
                                            </form>
                                        </div>
                                    </div>
                                    <form action="{{ route('expenses.destroy', $expense) }}" method="POST" onsubmit="return rpConfirm(event, 'Remove this expense?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-danger" style="background:none;border:0" title="Remove"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-cash-banknote fs-1"></i></span>
                                <p class="text-uppercase text-muted small mb-0">No expenses logged yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>

        <x-filter-modal name="expenses" :clear-url="route('expenses.index')">
            <div class="col-12">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All</option>
                    @foreach(\App\Http\Controllers\ExpenseController::CATEGORIES as $cat)
                        <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
        </x-filter-modal>
    </form>

    <div class="mt-3">{{ $expenses->links('vendor.pagination.rp-circles') }}</div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="rp-add-expense" @if($errors->any()) data-rp-autoshow @endif>
        <div class="offcanvas-header border-bottom">
            <h3 class="offcanvas-title">Log Expense</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <form action="{{ route('expenses.store') }}" method="POST" class="d-flex flex-column h-100">
            @csrf
            <div class="offcanvas-body">
                <div class="mb-3">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category" required class="form-select">
                        @foreach(\App\Http\Controllers\ExpenseController::CATEGORIES as $cat)
                            <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('category') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount ({{ $currency }}) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" required value="{{ old('amount') }}" placeholder="0.00" class="form-control">
                    @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="spent_at" required value="{{ old('spent_at', now()->format('Y-m-d')) }}" class="form-control">
                    @error('spent_at') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes <span class="text-muted text-lowercase">(optional)</span></label>
                    <textarea name="notes" rows="4" class="form-control">{{ old('notes') }}</textarea>
                    @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="offcanvas-footer p-3 border-top">
                <button type="submit" class="btn btn-primary w-100">Save Expense</button>
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
