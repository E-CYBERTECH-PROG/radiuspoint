<x-sidebar-layout title="Portal Announcements">
    <div class="mb-4">
        <h1 class="mb-1">Portal Announcements</h1>
        <p class="text-muted mb-0">Banners at the top of the WiFi login page — one router or all of them.</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Message</th>
                                <th>Router</th>
                                <th>Expires</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($announcements as $announcement)
                                <tr>
                                    <td>
                                        <x-status-badge :color="match($announcement->category) { 'maintenance' => 'amber', 'promo' => 'green', 'outage' => 'red', default => 'blue' }">{{ ucfirst($announcement->category) }}</x-status-badge>
                                        <p class="mt-2 mb-0">{{ $announcement->message }}</p>
                                    </td>
                                    <td class="text-muted">{{ $announcement->router?->name ?? 'All routers' }}</td>
                                    <td class="text-muted">
                                        @if($announcement->expires_at)
                                            {{ $announcement->isActive() ? $announcement->expires_at->diffForHumans() : 'Expired' }}
                                        @else
                                            Never
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('captive-announcements.destroy', $announcement) }}" method="POST" onsubmit="return rpConfirm(event, 'Remove this announcement?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-danger" style="background:none;border:0" title="Remove"><i class="ti ti-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">
                                        <i class="ti ti-broadcast icon icon-lg mb-2 d-block"></i>
                                        <p class="text-uppercase small mb-0">No announcements posted.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">Post an Announcement</h2>
                    <form action="{{ route('captive-announcements.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Router</label>
                            <select name="router_id" class="form-select">
                                <option value="">All routers</option>
                                @foreach($routers as $router)
                                    <option value="{{ $router->id }}">{{ $router->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                @foreach(\App\Models\CaptivePortalAnnouncement::CATEGORIES as $category)
                                    <option value="{{ $category }}">{{ ucfirst($category) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea name="message" required maxlength="280" rows="3" placeholder="e.g. Scheduled maintenance tonight 10pm-12am" class="form-control"></textarea>
                            @error('message') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expires At <span class="text-muted text-lowercase">(optional)</span></label>
                            <input type="datetime-local" name="expires_at" class="form-control">
                            <p class="text-muted small mt-1 mb-0">Leave blank to show until manually removed.</p>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Post Announcement</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-sidebar-layout>
