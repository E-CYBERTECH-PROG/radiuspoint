<x-sidebar-layout title="Deploy Router">
    <div class="mb-3">
        <a href="{{ route('routers.index') }}" class="d-inline-flex align-items-center gap-2 mb-2 small">
            <i class="ti ti-arrow-left icon"></i> Back to Routerboards
        </a>
        <h2 class="mb-1">Deploy Hardware</h2>
        <p class="text-muted small mb-0">Connect a new MikroTik router automatically.</p>
    </div>

    <div class="card">
        <div class="row g-0">
            <div class="col-lg-4 order-2 order-lg-1 bg-body-secondary border-end p-3 p-lg-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="avatar avatar-sm bg-primary-lt"><i class="ti ti-robot fs-4"></i></span>
                    <h4 class="mb-0">Auto-Provisioning</h4>
                </div>
                <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                    <li class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-sm bg-primary-lt flex-shrink-0"><i class="ti ti-wand"></i></span>
                        <span class="small"><strong>IP:</strong> Allocated automatically.</span>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-sm bg-green-lt flex-shrink-0"><i class="ti ti-shield-check"></i></span>
                        <span class="small"><strong>Credentials:</strong> Generated automatically.</span>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-sm bg-azure-lt flex-shrink-0"><i class="ti ti-terminal-2"></i></span>
                        <span class="small"><strong>Setup:</strong> One script to paste into the terminal.</span>
                    </li>
                </ul>
            </div>

            <div class="col-lg-8 order-1 order-lg-2 p-3 p-lg-4">
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

                    <div class="pt-4 mt-3 border-top d-flex flex-column-reverse flex-sm-row align-items-stretch align-items-sm-center justify-content-sm-end gap-2 gap-sm-3">
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
