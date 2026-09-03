<x-sidebar-layout title="Deploy Router">
    <div class="mb-4">
        <a href="{{ route('routers.index') }}" class="d-inline-flex align-items-center gap-2 mb-2">
            <i class="ti ti-arrow-left icon"></i> Back to Hardware &amp; Routers
        </a>
        <h1 class="mb-1">Deploy Hardware</h1>
        <p class="text-muted mb-0">Connect a new MikroTik router automatically.</p>
    </div>

    <div class="card">
        <div class="row g-0">
            <div class="col-lg-4 bg-body-secondary border-end p-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="avatar bg-primary-lt"><i class="ti ti-robot fs-3"></i></span>
                    <h3 class="mb-0">Auto-Provisioning</h3>
                </div>
                <ul class="list-unstyled d-flex flex-column gap-3">
                    <li class="d-flex align-items-center gap-3">
                        <span class="avatar avatar-sm bg-primary-lt flex-shrink-0"><i class="ti ti-wand"></i></span>
                        <span><strong>IP:</strong> Allocated automatically.</span>
                    </li>
                    <li class="d-flex align-items-center gap-3">
                        <span class="avatar avatar-sm bg-green-lt flex-shrink-0"><i class="ti ti-shield-check"></i></span>
                        <span><strong>Credentials:</strong> Generated automatically.</span>
                    </li>
                    <li class="d-flex align-items-center gap-3">
                        <span class="avatar avatar-sm bg-azure-lt flex-shrink-0"><i class="ti ti-terminal-2"></i></span>
                        <span><strong>Setup:</strong> One script to paste into the terminal.</span>
                    </li>
                </ul>
            </div>

            <div class="col-lg-8 p-4">
                <form action="{{ route('routers.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label">Working Label</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-map-pin"></i></span>
                            <input type="text" name="name" required placeholder="e.g., Kileleshwa Base Station" class="form-control form-control-lg">
                        </div>
                        <p class="text-muted small mt-2">A temporary name — replaced automatically once the router connects.</p>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">RouterOS Version</label>
                        <select name="routeros_version" required class="form-select">
                            <option value="v7">v7 and above</option>
                            <option value="v6">v6.48.5 and above</option>
                        </select>
                        <p class="text-muted small mt-2">The provisioning script's tunnel setup differs between RouterOS versions.</p>
                    </div>

                    <div class="pt-4 mt-3 border-top d-flex align-items-center justify-content-end gap-3">
                        <a href="{{ route('routers.index') }}" class="btn btn-link">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Generate Script <i class="ti ti-arrow-right icon"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-sidebar-layout>
