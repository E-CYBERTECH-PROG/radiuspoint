<x-sidebar-layout title="Portal Announcements">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Portal Announcements</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Banners at the top of the WiFi login page — one router or all of them.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                            <th class="px-6 py-4">Message</th>
                            <th class="px-6 py-4">Router</th>
                            <th class="px-6 py-4">Expires</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        @forelse($announcements as $announcement)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                <td class="px-6 py-4">
                                    <x-status-badge :color="match($announcement->category) { 'maintenance' => 'amber', 'promo' => 'green', 'outage' => 'red', default => 'blue' }">{{ ucfirst($announcement->category) }}</x-status-badge>
                                    <p class="text-gray-700 dark:text-gray-300 mt-1.5">{{ $announcement->message }}</p>
                                </td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $announcement->router?->name ?? 'All routers' }}</td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                    @if($announcement->expires_at)
                                        {{ $announcement->isActive() ? $announcement->expires_at->diffForHumans() : 'Expired' }}
                                    @else
                                        Never
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="{{ route('captive-announcements.destroy', $announcement) }}" method="POST" onsubmit="return confirm('Remove this announcement?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Remove"><i class="bx bx-trash text-lg"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                    <i class="bx bx-broadcast text-4xl mb-3 text-gray-200"></i>
                                    <p class="text-xs tracking-widest uppercase">No announcements posted.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 h-fit">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-4">Post an Announcement</h2>
            <form action="{{ route('captive-announcements.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Router</label>
                    <select name="router_id" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-4 rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All routers</option>
                        @foreach($routers as $router)
                            <option value="{{ $router->id }}">{{ $router->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Category</label>
                    <select name="category" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-4 rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(\App\Models\CaptivePortalAnnouncement::CATEGORIES as $category)
                            <option value="{{ $category }}">{{ ucfirst($category) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Message <span class="text-red-500">*</span></label>
                    <textarea name="message" required maxlength="280" rows="3" placeholder="e.g. Scheduled maintenance tonight 10pm-12am" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-4 rounded-lg outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    @error('message') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Expires At <span class="text-gray-400 normal-case">(optional)</span></label>
                    <input type="datetime-local" name="expires_at" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2.5 px-4 rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Leave blank to show until manually removed.</p>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-sm transition-colors">Post Announcement</button>
            </form>
        </div>
    </div>
</x-sidebar-layout>
