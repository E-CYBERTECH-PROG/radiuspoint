<x-sidebar-layout title="Edit PPPoE Customer">
    <div class="mb-6">
        <a href="{{ route('pppoe-users.index') }}" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors inline-flex items-center gap-2 mb-2">
            <i class="bx bx-left-arrow-alt text-lg"></i> Back to PPPoE Users
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit PPPoE Customer</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Update this customer's details.</p>
    </div>

    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 max-w-4xl mb-6">
        <h2 class="text-md font-bold text-gray-900 dark:text-white mb-5">Quick Actions</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Data Usage This Cycle</p>
                @if($usage)
                    <p class="text-lg font-fira font-bold text-gray-900 dark:text-white">
                        {{ number_format($usage['used_mb']) }} MB{{ $usage['cap_mb'] ? ' / ' . number_format($usage['cap_mb']) . ' MB' : '' }}
                        @if($usage['throttled'])
                            <span class="text-xs font-bold text-red-500 uppercase tracking-wide align-middle ml-1">Throttled</span>
                        @endif
                    </p>
                    @if($usage['percent'] !== null)
                        <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full mt-2 overflow-hidden">
                            <div class="h-full {{ $usage['percent'] >= 100 ? 'bg-red-500' : ($usage['percent'] >= 80 ? 'bg-amber-500' : 'bg-blue-500') }}" style="width: {{ $usage['percent'] }}%"></div>
                        </div>
                    @endif
                    <p class="text-xs text-gray-400 mt-1">Since {{ $usage['cycle_start']->format('d M Y H:i') }}</p>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No plan assigned yet.</p>
                @endif
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Current Expiry</p>
                <p class="text-lg font-fira font-bold text-gray-900 dark:text-white">
                    {{ $pppoe_user->expires_at?->format('d M Y H:i') ?? '—' }}
                </p>
                @if($pppoe_user->expires_at)
                    <p class="text-xs {{ $pppoe_user->expires_at->isPast() ? 'text-red-500' : 'text-gray-400' }} mt-1">
                        {{ $pppoe_user->expires_at->diffForHumans() }}
                    </p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-100 dark:border-gray-800">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Extend</p>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach([1 => '+1 Day', 7 => '+7 Days', 30 => '+30 Days'] as $days => $label)
                        <form action="{{ route('pppoe-users.extend', $pppoe_user) }}" method="POST">
                            @csrf
                            <input type="hidden" name="days" value="{{ $days }}">
                            <button type="submit" class="text-xs font-bold bg-gray-100 dark:bg-gray-900 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 text-gray-700 dark:text-gray-300 py-2 px-3 rounded-lg transition-colors">{{ $label }}</button>
                        </form>
                    @endforeach
                    <form action="{{ route('pppoe-users.extend', $pppoe_user) }}" method="POST" class="flex items-center gap-2">
                        @csrf
                        <input type="datetime-local" name="expires_at" class="text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-2 px-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        <button type="submit" class="text-xs font-bold bg-gray-100 dark:bg-gray-900 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 text-gray-700 dark:text-gray-300 py-2 px-3 rounded-lg transition-colors">Set Date</button>
                    </form>
                </div>
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Session</p>
                <form action="{{ route('pppoe-users.disconnect', $pppoe_user) }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs font-bold bg-gray-100 dark:bg-gray-900 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:text-amber-600 text-gray-700 dark:text-gray-300 py-2 px-3 rounded-lg transition-colors inline-flex items-center gap-1.5"><i class="bx bx-power-off"></i> Force Disconnect</button>
                </form>
            </div>
        </div>
    </div>

    <form action="{{ route('pppoe-users.update', $pppoe_user) }}" method="POST" class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 space-y-8 max-w-4xl">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Username <span class="text-red-500">*</span></label>
                <input type="text" name="username" required value="{{ old('username', $pppoe_user->username) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                @error('username') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Full Name</label>
                <input type="text" name="name" value="{{ old('name', $pppoe_user->name) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Phone Number</label>
                <input type="tel" name="phone_number" value="{{ old('phone_number', $pppoe_user->phone_number) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Package</label>
                <select name="current_plan_id" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    <option value="">— None —</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected($pppoe_user->current_plan_id == $plan->id)>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Router</label>
                <select name="current_router_id" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    <option value="">— None —</option>
                    @foreach($routers as $router)
                        <option value="{{ $router->id }}" @selected($pppoe_user->current_router_id == $router->id)>{{ $router->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Status <span class="text-red-500">*</span></label>
                <select name="status" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors cursor-pointer">
                    <option value="active" @selected($pppoe_user->status == 'active')>Active</option>
                    <option value="expired" @selected($pppoe_user->status == 'expired')>Expired</option>
                    <option value="offline" @selected($pppoe_user->status == 'offline')>Offline</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Expires At</label>
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $pppoe_user->expires_at?->format('Y-m-d\TH:i')) }}" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3.5 px-4 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
            </div>
        </div>

        <div class="pt-6 border-t border-gray-100 dark:border-gray-800 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors inline-flex items-center gap-2">
                <i class="bx bx-save text-lg"></i> Update Customer
            </button>
        </div>
    </form>
</x-sidebar-layout>
