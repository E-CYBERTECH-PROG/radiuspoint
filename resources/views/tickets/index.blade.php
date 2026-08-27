<x-sidebar-layout title="Support Tickets">
    <div x-data="{ open: false, editOpen: null }" x-init="open = @json($errors->any())">
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Support Tickets</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Customer issues and support requests.</p>
            </div>
            <button @click="open = true" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                <i class="bx bx-plus text-lg"></i> Add Ticket
            </button>
        </div>

        <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3">
            <div class="flex items-center bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 flex-1">
                <i class="bx bx-search text-gray-400 text-lg"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search username or phone..." class="bg-transparent border-none outline-none focus:ring-0 text-sm ml-2 w-full dark:text-gray-200 dark:placeholder-gray-500">
            </div>
            <select name="status" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
                <option value="">All Statuses</option>
                @foreach(['open', 'in_progress', 'resolved', 'closed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <x-per-page-select />
            <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white text-sm font-bold px-5 py-2 rounded-lg">Filter</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('tickets.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 self-center">Clear</a>
            @endif
        </form>

        <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                            <th class="px-6 py-4">Username / Phone</th>
                            <th class="px-6 py-4">Notes</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        @forelse($tickets as $ticket)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-gray-900 dark:text-white font-bold">{{ $ticket->username }}</span>
                                        @if($ticket->phone)
                                            <span class="text-[10px] text-gray-400">{{ $ticket->phone }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400 max-w-md">{{ $ticket->notes }}</td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $ticket->created_at->format('d M Y H:i') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <form action="{{ route('tickets.update-status', $ticket) }}" method="POST" class="inline-block">
                                        @csrf @method('PATCH')
                                        <select name="status" onchange="this.form.submit()" class="text-xs font-semibold rounded-full px-2.5 py-0.5 border-0 outline-none cursor-pointer {{ $ticket->status == 'resolved' || $ticket->status == 'closed' ? 'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400' : ($ticket->status == 'in_progress' ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-amber-100 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400') }}">
                                            <option value="open" @selected($ticket->status == 'open')>Open</option>
                                            <option value="in_progress" @selected($ticket->status == 'in_progress')>In Progress</option>
                                            <option value="resolved" @selected($ticket->status == 'resolved')>Resolved</option>
                                            <option value="closed" @selected($ticket->status == 'closed')>Closed</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="px-6 py-4 text-right relative">
                                    <div class="flex items-center justify-end gap-3">
                                        <button @click="editOpen = {{ $ticket->id }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="Edit"><i class="bx bx-edit-alt text-lg"></i></button>
                                        <form action="{{ route('tickets.destroy', $ticket) }}" method="POST" onsubmit="return rpConfirm(event, 'Remove this ticket?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Remove"><i class="bx bx-trash text-lg"></i></button>
                                        </form>
                                    </div>
                                    <div x-show="editOpen === {{ $ticket->id }}" x-cloak @click.outside="editOpen = null" class="absolute right-6 z-20 mt-2 w-72 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg shadow-lg p-4 text-left">
                                        <form action="{{ route('tickets.update', $ticket) }}" method="POST" class="space-y-3">
                                            @csrf @method('PUT')
                                            <div>
                                                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Username / Phone</label>
                                                <input type="text" name="username" required value="{{ $ticket->username }}" class="w-full text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-2 outline-none dark:text-white">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Phone</label>
                                                <input type="tel" name="phone" value="{{ $ticket->phone }}" class="w-full text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-2 outline-none dark:text-white">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Notes</label>
                                                <textarea name="notes" required rows="3" class="w-full text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-2 outline-none dark:text-white">{{ $ticket->notes }}</textarea>
                                            </div>
                                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 rounded-lg">Save Changes</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                    <i class="bx bx-support text-4xl mb-3 text-gray-200"></i>
                                    <p class="text-xs tracking-widest uppercase">No support tickets yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $tickets->links() }}</div>

        <div x-show="open" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-modal="true">
            <div class="absolute inset-0 bg-gray-900/60" @click="open = false" x-show="open" x-transition.opacity></div>
            <div class="absolute inset-y-0 right-0 max-w-full flex" x-show="open"
                 x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
                <div class="w-screen max-w-md bg-white dark:bg-gray-950 shadow-xl h-full flex flex-col">
                    <form action="{{ route('tickets.store') }}" method="POST" class="flex flex-col h-full">
                        @csrf
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Add Ticket</h2>
                            <button type="button" @click="open = false"><i class="bx bx-x text-2xl text-gray-400"></i></button>
                        </div>
                        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-5">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Username / Phone <span class="text-red-500">*</span></label>
                                <input type="text" name="username" required value="{{ old('username') }}" placeholder="username or 0712345678" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                @error('username') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Phone <span class="text-gray-400 normal-case">(optional)</span></label>
                                <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="0712345678" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Notes <span class="text-red-500">*</span></label>
                                <textarea name="notes" required rows="4" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">{{ old('notes') }}</textarea>
                                @error('notes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-sm transition-colors">Submit Ticket</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-sidebar-layout>
