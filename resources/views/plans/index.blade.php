{{-- Expects $plans (paginated, current $tab's Plan list), $activeRouterCount, $syncCounts,
     $tab ('pppoe'|'hotspot'), $pppoeCount, $hotspotCount, $routers, $planRouterIds (plan id =>
     array of router ids, for the Edit offcanvas) in scope. --}}
<x-sidebar-layout title="Packages">
    @php
        $oneispEditingPlanId = old('_editing_plan_id', request('edit'));
    @endphp

    {{-- === TABS === --}}
    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a href="{{ route('plans.index', array_filter(['tab' => 'pppoe', 'search' => request('search')])) }}" class="nav-link {{ $tab === 'pppoe' ? 'active' : '' }}">
                PPPoE ({{ $pppoeCount }})
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('plans.index', array_filter(['tab' => 'hotspot', 'search' => request('search')])) }}" class="nav-link {{ $tab === 'hotspot' ? 'active' : '' }}">
                Hotspot ({{ $hotspotCount }})
            </a>
        </li>
    </ul>

    {{-- === ADD PACKAGE OFFCANVAS === --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="rp-add-plan" @if($errors->any() && !$oneispEditingPlanId) data-rp-autoshow @endif>
        <div class="offcanvas-header border-bottom">
            <h3 class="offcanvas-title">Add Package</h3>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <form action="{{ route('plans.store') }}" method="POST" class="d-flex flex-column h-100">
            @csrf
            <div class="offcanvas-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Package Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" required value="{{ old('name') }}" placeholder="e.g., 10Mbps Premium Home" class="form-control">
                        @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Package Type <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-selectgroup-item w-100">
                                    <input type="radio" name="type" value="hotspot" class="form-selectgroup-input" @checked(old('type', $tab) === 'hotspot')>
                                    <span class="form-selectgroup-label text-center"><i class="ti ti-broadcast"></i> Hotspot</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="form-selectgroup-item w-100">
                                    <input type="radio" name="type" value="pppoe" class="form-selectgroup-input" @checked(old('type', $tab) === 'pppoe')>
                                    <span class="form-selectgroup-label text-center"><i class="ti ti-device-desktop"></i> PPPoE (Fixed)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <label class="form-label">Upload Speed <span class="text-danger">*</span></label>
                        <input type="text" name="upload_speed" required pattern="\d+[kKmM]" title="Number followed by K or M — e.g. 5M." value="{{ old('upload_speed') }}" placeholder="5M" class="form-control">
                        @error('upload_speed') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label">Download Speed <span class="text-danger">*</span></label>
                        <input type="text" name="download_speed" required pattern="\d+[kKmM]" title="Number followed by K or M — e.g. 5M." value="{{ old('download_speed') }}" placeholder="5M" class="form-control">
                        @error('download_speed') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label mb-1">Burst <span class="text-muted fw-normal small">(optional — short speed boost that makes browsing feel faster)</span></label>
                        <div class="row g-2">
                            <div class="col-4"><div class="text-muted small mb-1">Upload</div><input type="text" name="burst_upload" pattern="\d+[kKmM]" title="Number followed by K or M — e.g. 10M." value="{{ old('burst_upload') }}" placeholder="10M" class="form-control"></div>
                            <div class="col-4"><div class="text-muted small mb-1">Download</div><input type="text" name="burst_download" pattern="\d+[kKmM]" title="Number followed by K or M — e.g. 10M." value="{{ old('burst_download') }}" placeholder="10M" class="form-control"></div>
                            <div class="col-4"><div class="text-muted small mb-1">For</div><div class="input-group"><input type="number" name="burst_time" min="1" max="60" value="{{ old('burst_time') }}" placeholder="8" class="form-control"><span class="input-group-text">sec</span></div></div>
                        </div>
                        @error('burst_upload') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        @error('burst_download') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-6">
                        <label class="form-label">Period <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="duration_value" required min="1" value="{{ old('duration_value') }}" placeholder="30" class="form-control">
                            <select name="duration_unit" required class="form-select">
                                @foreach(\App\Models\Plan::DURATION_UNITS as $unit)
                                    <option value="{{ $unit }}" @selected(old('duration_unit', 'days') === $unit)>{{ ucfirst($unit) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-6">
                        <label class="form-label">Price (KES) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">KES</span>
                            <input type="number" name="price" required min="0" step="0.01" value="{{ old('price') }}" placeholder="2500" class="form-control">
                        </div>
                        @error('price') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="offcanvas-footer p-3 border-top">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-cloud-upload icon"></i> Save &amp; Sync to Hardware
                </button>
            </div>
        </form>
    </div>

    {{-- === TABLE CARD === --}}
    <form method="GET">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="card">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom">
            <div class="d-flex align-items-center gap-2" id="rp-plans-pager">
                <span class="text-muted small">Show</span>
                <x-per-page-select :default="10" />
                <span class="text-muted small">Entries</span>
            </div>
            <div class="d-none align-items-center gap-2" id="rp-plans-bulkbar">
                <span class="fw-bold small"><span id="rp-plans-count">0</span> selected</span>
                <button type="button" class="btn btn-sm" data-rp-bulk="activate"><i class="ti ti-player-play icon"></i> Activate</button>
                <button type="button" class="btn btn-sm" data-rp-bulk="deactivate"><i class="ti ti-player-pause icon"></i> Deactivate</button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-rp-bulk="delete"><i class="ti ti-trash icon"></i> Delete</button>
            </div>

            <div class="d-flex align-items-center gap-2">
                <div class="input-icon">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search…" class="form-control">
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#rp-add-plan">
                    <i class="ti ti-plus icon"></i> <span class="d-none d-sm-inline">New Package</span>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap">
                <thead>
                    <tr>
                        <th class="w-1"><input type="checkbox" class="form-check-input m-0" id="rp-plans-select-all" title="Select all" aria-label="Select all packages"></th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created On</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $i => $plan)
                        <tr>
                            <td><input type="checkbox" class="form-check-input m-0 rp-plan-check" name="plan_ids[]" value="{{ $plan->id }}" form="rp-plans-bulk" aria-label="Select {{ $plan->name }}"></td>
                            <td class="text-muted">{{ $plans->firstItem() + $i }}</td>
                            <td class="fw-bold">{{ $plan->name }}</td>
                            <td>KES {{ number_format($plan->price) }}</td>
                            <td>
                                <span class="text-muted d-inline-flex align-items-center gap-1">
                                    <i class="ti {{ $plan->type === 'hotspot' ? 'ti-broadcast' : 'ti-device-desktop' }}"></i>
                                    {{ $plan->type === 'hotspot' ? 'Hotspot' : 'Fixed' }}
                                </span>
                            </td>
                            <td>
                                @if($plan->status === 'active')
                                    <x-status-badge color="green">Active</x-status-badge>
                                @else
                                    <x-status-badge color="gray">Inactive</x-status-badge>
                                @endif
                            </td>
                            <td class="text-muted">{{ $plan->created_at->format('H:i M d, Y') }}</td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-3">
                                    <button type="button" class="text-muted" style="background:none;border:0" data-bs-toggle="offcanvas" data-bs-target="#rp-edit-plan-{{ $plan->id }}" title="Edit"><i class="ti ti-edit"></i></button>
                                    <button type="submit" form="rp-plan-dup-{{ $plan->id }}" class="text-muted" style="background:none;border:0" title="Duplicate"><i class="ti ti-copy"></i></button>
                                    <a href="{{ route('plans.sync-status', $plan) }}" class="text-success" title="View Sync Status"><i class="ti ti-eye"></i></a>
                                    <button type="submit" form="rp-plan-del-{{ $plan->id }}" class="text-danger" style="background:none;border:0" title="Delete"><i class="ti ti-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <span class="avatar avatar-xl bg-primary-lt mb-3"><i class="ti ti-box fs-1"></i></span>
                                <p class="text-uppercase text-muted small mb-0">No {{ $tab === 'hotspot' ? 'hotspot' : 'fixed' }} packages yet — add your first one to get started.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">{{ $plans->links('vendor.pagination.rp-circles') }}</div>
    </div>
    </form>

    {{-- Row and bulk action forms live outside the search form above: a <form> nested inside
         another is ignored by the browser, so its button would submit the search instead.
         The table's buttons/checkboxes reach these through the form="…" attribute. --}}
    @foreach($plans as $plan)
        <form id="rp-plan-dup-{{ $plan->id }}" action="{{ route('plans.duplicate', $plan) }}" method="POST" class="d-none">@csrf</form>
        <form id="rp-plan-del-{{ $plan->id }}" action="{{ route('plans.destroy', $plan) }}" method="POST" class="d-none" onsubmit="return rpConfirm(event, 'Delete this plan? Customers assigned to it must be reassigned first.')">@csrf @method('DELETE')</form>
    @endforeach
    <form id="rp-plans-bulk" action="{{ route('plans.bulk') }}" method="POST" class="d-none">
        @csrf
        <input type="hidden" name="action" id="rp-plans-bulk-action">
        <input type="hidden" name="tab" value="{{ $tab }}">
    </form>

    {{-- === EDIT PACKAGE OFFCANVASES — one per row === --}}
    @foreach($plans as $plan)
        @php
            [$oneispUpload, $oneispDownload] = array_pad(explode('/', $plan->speed_limit ?? ''), 2, '');
            $oneispSelectedRouterIds = old('_editing_plan_id') == $plan->id
                ? old('router_ids', [])
                : ($planRouterIds[$plan->id] ?? []);
            $oneispEditingThis = old('_editing_plan_id') == $plan->id;
        @endphp
        <div class="offcanvas offcanvas-end" tabindex="-1" id="rp-edit-plan-{{ $plan->id }}" @if($oneispEditingPlanId == $plan->id) data-rp-autoshow @endif>
            <div class="offcanvas-header border-bottom">
                <h3 class="offcanvas-title">Edit Package</h3>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>

            <form action="{{ route('plans.update', $plan) }}" method="POST" class="d-flex flex-column h-100">
                @csrf @method('PUT')
                <input type="hidden" name="_editing_plan_id" value="{{ $plan->id }}">

                <div class="offcanvas-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Package Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" required value="{{ $oneispEditingThis ? old('name') : $plan->name }}" class="form-control">
                            @if($oneispEditingThis) @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror @endif
                        </div>

                        <div class="col-12">
                            <label class="form-label">Package Type <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-selectgroup-item w-100">
                                        <input type="radio" name="type" value="hotspot" class="form-selectgroup-input" @checked(($oneispEditingThis ? old('type') : $plan->type) === 'hotspot')>
                                        <span class="form-selectgroup-label text-center"><i class="ti ti-broadcast"></i> Hotspot</span>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <label class="form-selectgroup-item w-100">
                                        <input type="radio" name="type" value="pppoe" class="form-selectgroup-input" @checked(($oneispEditingThis ? old('type') : $plan->type) === 'pppoe')>
                                        <span class="form-selectgroup-label text-center"><i class="ti ti-device-desktop"></i> PPPoE (Fixed)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" required class="form-select">
                                <option value="active" @selected(($oneispEditingThis ? old('status') : $plan->status) === 'active')>Active</option>
                                <option value="inactive" @selected(($oneispEditingThis ? old('status') : $plan->status) === 'inactive')>Inactive</option>
                            </select>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Price (KES) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">KES</span>
                                <input type="number" name="price" required min="0" step="0.01" value="{{ $oneispEditingThis ? old('price') : $plan->price }}" class="form-control">
                            </div>
                            @if($oneispEditingThis) @error('price') <div class="text-danger small mt-1">{{ $message }}</div> @enderror @endif
                        </div>

                        <div class="col-6">
                            <label class="form-label">Upload Speed <span class="text-danger">*</span></label>
                            <input type="text" name="upload_speed" required pattern="\d+[kKmM]" title="Number followed by K or M — e.g. 5M." value="{{ $oneispEditingThis ? old('upload_speed') : $oneispUpload }}" placeholder="5M" class="form-control">
                            @if($oneispEditingThis) @error('upload_speed') <div class="text-danger small mt-1">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-6">
                            <label class="form-label">Download Speed <span class="text-danger">*</span></label>
                            <input type="text" name="download_speed" required pattern="\d+[kKmM]" title="Number followed by K or M — e.g. 5M." value="{{ $oneispEditingThis ? old('download_speed') : $oneispDownload }}" placeholder="5M" class="form-control">
                            @if($oneispEditingThis) @error('download_speed') <div class="text-danger small mt-1">{{ $message }}</div> @enderror @endif
                        </div>

                        @php [$oneispBurstUp, $oneispBurstDown] = array_pad(explode('/', $plan->burst_limit ?? ''), 2, ''); @endphp
                        <div class="col-12">
                            <label class="form-label mb-1">Burst <span class="text-muted fw-normal small">(optional — leave empty for none)</span></label>
                            <div class="row g-2">
                                <div class="col-4"><div class="text-muted small mb-1">Upload</div><input type="text" name="burst_upload" pattern="\d+[kKmM]" title="Number followed by K or M — e.g. 10M." value="{{ $oneispEditingThis ? old('burst_upload') : $oneispBurstUp }}" placeholder="10M" class="form-control"></div>
                                <div class="col-4"><div class="text-muted small mb-1">Download</div><input type="text" name="burst_download" pattern="\d+[kKmM]" title="Number followed by K or M — e.g. 10M." value="{{ $oneispEditingThis ? old('burst_download') : $oneispBurstDown }}" placeholder="10M" class="form-control"></div>
                                <div class="col-4"><div class="text-muted small mb-1">For</div><div class="input-group"><input type="number" name="burst_time" min="1" max="60" value="{{ $oneispEditingThis ? old('burst_time') : $plan->burst_time }}" placeholder="8" class="form-control"><span class="input-group-text">sec</span></div></div>
                            </div>
                            @if($oneispEditingThis) @error('burst_upload') <div class="text-danger small mt-1">{{ $message }}</div> @enderror @error('burst_download') <div class="text-danger small mt-1">{{ $message }}</div> @enderror @endif
                        </div>

                        <div class="col-6">
                            <label class="form-label">Period <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="duration_value" required min="1" value="{{ $oneispEditingThis ? old('duration_value') : $plan->duration_value }}" class="form-control">
                                <select name="duration_unit" required class="form-select">
                                    @foreach(\App\Models\Plan::DURATION_UNITS as $unit)
                                        <option value="{{ $unit }}" @selected(($oneispEditingThis ? old('duration_unit') : $plan->duration_unit) === $unit)>{{ ucfirst($unit) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Data Cap (MB) <span class="text-muted text-lowercase">(optional)</span></label>
                            <input type="number" name="data_cap_mb" min="1" value="{{ $oneispEditingThis ? old('data_cap_mb') : $plan->data_cap_mb }}" placeholder="Unlimited if blank" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Fair Use Speed <span class="text-muted text-lowercase">(optional — throttled speed once the data cap is hit)</span></label>
                            <input type="text" name="fup_speed_limit" pattern="\d+[kKmM]/\d+[kKmM]" title="Number + K or M, a slash, then number + K or M — e.g. 1M/1M." value="{{ $oneispEditingThis ? old('fup_speed_limit') : $plan->fup_speed_limit }}" placeholder="1M/1M" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Portal Caption <span class="text-muted text-lowercase">(optional)</span></label>
                            <input type="text" name="caption" maxlength="255" value="{{ $oneispEditingThis ? old('caption') : $plan->caption }}" placeholder="e.g. Best for streaming Netflix" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Applies To <span class="text-muted text-lowercase">(optional — leave all unchecked for every router)</span></label>
                            @if($routers->isEmpty())
                                <p class="text-muted small">No routers deployed yet — this plan will sync to any router added later.</p>
                            @else
                                <div class="row g-2">
                                    @foreach($routers as $router)
                                        <div class="col-6">
                                            <label class="form-check">
                                                <input type="checkbox" name="router_ids[]" value="{{ $router->id }}" @checked(in_array($router->id, $oneispSelectedRouterIds)) class="form-check-input">
                                                <span class="form-check-label">{{ $router->name }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="offcanvas-footer p-3 border-top">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-cloud-upload icon"></i> Save &amp; Re-Sync to Hardware
                    </button>
                </div>
            </form>
        </div>
    @endforeach

    <script>
        (function () {
            var all = document.getElementById('rp-plans-select-all');
            var checks = function () { return Array.prototype.slice.call(document.querySelectorAll('.rp-plan-check')); };
            var bar = document.getElementById('rp-plans-bulkbar'), pager = document.getElementById('rp-plans-pager');
            var form = document.getElementById('rp-plans-bulk'), actionInput = document.getElementById('rp-plans-bulk-action');
            if (!all || !form) return;

            function refresh() {
                var n = checks().filter(function (c) { return c.checked; }).length;
                document.getElementById('rp-plans-count').textContent = n;
                bar.classList.toggle('d-none', n === 0); bar.classList.toggle('d-flex', n > 0);
                pager.classList.toggle('d-none', n > 0); pager.classList.toggle('d-flex', n === 0);
                all.checked = n > 0 && n === checks().length;
                all.indeterminate = n > 0 && n < checks().length;
            }
            all.addEventListener('change', function () { checks().forEach(function (c) { c.checked = all.checked; }); refresh(); });
            document.addEventListener('change', function (e) { if (e.target.classList.contains('rp-plan-check')) refresh(); });

            document.querySelectorAll('[data-rp-bulk]').forEach(function (btn) {
                btn.addEventListener('click', async function () {
                    var action = btn.getAttribute('data-rp-bulk');
                    var n = document.getElementById('rp-plans-count').textContent;
                    if (action === 'delete' && !(await window.rpConfirmAsync('Delete ' + n + ' package(s)? Any still assigned to customers will be skipped.'))) return;
                    actionInput.value = action;
                    if (window.rpShowPageLoader) window.rpShowPageLoader();
                    HTMLFormElement.prototype.submit.call(form);
                });
            });
        })();

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-rp-autoshow]').forEach(function (el) {
                bootstrap.Offcanvas.getOrCreateInstance(el).show();
            });
        });
    </script>
</x-sidebar-layout>
