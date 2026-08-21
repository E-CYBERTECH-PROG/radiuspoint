<x-sidebar-layout title="Team">
    <div x-data="{ open: false, editOpen: null }" x-init="open = @json($errors->any())">
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Team</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Staff accounts with access to your RadiusPoint dashboard.</p>
            </div>
            <button @click="open = true" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                <i class="bx bx-user-plus text-lg"></i> Add Team Member
            </button>
        </div>

        <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3">
            <div class="flex items-center bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg px-3 py-2 flex-1">
                <i class="bx bx-search text-gray-400 text-lg"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email..." class="bg-transparent border-none outline-none focus:ring-0 text-sm ml-2 w-full dark:text-gray-200 dark:placeholder-gray-500">
            </div>
            <select name="role" class="bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg text-sm px-3 py-2 text-gray-700 dark:text-gray-300 outline-none">
                <option value="">All Roles</option>
                @foreach(['Admin', 'Technician', 'Sales Agent'] as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>
                @endforeach
            </select>
            <x-per-page-select />
            <button type="submit" class="bg-gray-900 dark:bg-gray-700 text-white text-sm font-bold px-5 py-2 rounded-lg">Filter</button>
            @if(request()->hasAny(['search', 'role']))
                <a href="{{ route('team.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 self-center">Clear</a>
            @endif
        </form>

        <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold">
                            <th class="px-6 py-4">Name</th>
                            <th class="px-6 py-4">Email</th>
                            <th class="px-6 py-4">Role</th>
                            <th class="px-6 py-4">Added</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        @forelse($members as $member)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                <td class="px-6 py-4 text-gray-900 dark:text-white font-bold">{{ $member->name }}</td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $member->email }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-[10px] tracking-wide uppercase font-bold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-900/50">{{ $member->role }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $member->created_at->format('d M Y') }}</td>
                                <td class="px-6 py-4 text-right relative">
                                    @if($member->role !== 'SuperAdmin')
                                        <div class="flex items-center justify-end gap-3">
                                            <button @click="editOpen = {{ $member->id }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="Edit"><i class="bx bx-edit-alt text-lg"></i></button>
                                            <form action="{{ route('team.reset-password', $member) }}" method="POST" onsubmit="return rpConfirm(event, 'Reset password for {{ $member->name }}? A new temporary password will be generated.')">
                                                @csrf
                                                <button type="submit" class="text-gray-400 hover:text-amber-600 transition-colors" title="Reset Password"><i class="bx bx-key text-lg"></i></button>
                                            </form>
                                            @if($member->id !== Auth::id())
                                                <form action="{{ route('team.destroy', $member) }}" method="POST" onsubmit="return rpConfirm(event, 'Remove this team member?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Remove"><i class="bx bx-trash text-lg"></i></button>
                                                </form>
                                            @endif
                                        </div>
                                        <div x-show="editOpen === {{ $member->id }}" x-cloak @click.outside="editOpen = null" class="absolute right-6 z-20 mt-2 w-72 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-lg shadow-lg p-4 text-left">
                                            <form action="{{ route('team.update', $member) }}" method="POST" class="space-y-3">
                                                @csrf @method('PUT')
                                                <div>
                                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Name</label>
                                                    <input type="text" name="name" required value="{{ $member->name }}" class="w-full text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-2 outline-none dark:text-white">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Email</label>
                                                    <input type="email" name="email" required value="{{ $member->email }}" class="w-full text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-2 outline-none dark:text-white">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Role</label>
                                                    <select name="role" required class="w-full text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-2 outline-none dark:text-white">
                                                        @foreach(['Admin', 'Technician', 'Sales Agent'] as $role)
                                                            <option value="{{ $role }}" @selected($member->role === $role)>{{ $role }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 rounded-lg">Save Changes</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                    <i class="bx bx-group text-4xl mb-3 text-gray-200"></i>
                                    <p class="text-xs tracking-widest uppercase">No team members yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $members->links() }}</div>

        <div x-show="open" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-modal="true">
            <div class="absolute inset-0 bg-gray-900/60" @click="open = false" x-show="open" x-transition.opacity></div>
            <div class="absolute inset-y-0 right-0 max-w-full flex" x-show="open"
                 x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
                <div class="w-screen max-w-md bg-white dark:bg-gray-950 shadow-xl h-full flex flex-col">
                    <form action="{{ route('team.store') }}" method="POST" class="flex flex-col h-full">
                        @csrf
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Add Team Member</h2>
                            <button type="button" @click="open = false"><i class="bx bx-x text-2xl text-gray-400"></i></button>
                        </div>
                        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-5">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" required value="{{ old('name') }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Email <span class="text-red-500">*</span></label>
                                <input type="email" name="email" required value="{{ old('email') }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Role <span class="text-red-500">*</span></label>
                                <select name="role" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="Admin">Admin</option>
                                    <option value="Technician">Technician</option>
                                    <option value="Sales Agent">Sales Agent</option>
                                </select>
                            </div>
                            <p class="text-xs text-gray-400">A random password will be generated and shown once after submitting — copy it immediately.</p>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-sm transition-colors">Add Member</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-sidebar-layout>
