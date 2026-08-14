<div class="bg-white dark:bg-gray-950 border border-gray-300/70 dark:border-green-900/40 p-5 rounded-xl shadow-sm rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <h3 class="text-md font-bold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('pppoe-users.create') }}" class="flex flex-col items-center justify-center p-3.5 hover:bg-gray-50 dark:hover:bg-gray-900 rounded-lg transition-colors group">
            <i class="bx bx-user-plus text-2xl text-gray-400 group-hover:text-blue-600 mb-1"></i>
            <span class="text-xs font-bold text-gray-600 dark:text-gray-300 group-hover:text-blue-600">Add User</span>
        </a>
        <a href="{{ route('hotspot-users.index') }}" class="flex flex-col items-center justify-center p-3.5 hover:bg-gray-50 dark:hover:bg-gray-900 rounded-lg transition-colors group">
            <i class="bx bx-wallet text-2xl text-gray-400 group-hover:text-green-600 mb-1"></i>
            <span class="text-xs font-bold text-gray-600 dark:text-gray-300 group-hover:text-green-600">Recharge</span>
        </a>
        <a href="{{ route('tickets.index') }}" class="flex flex-col items-center justify-center p-3.5 hover:bg-gray-50 dark:hover:bg-gray-900 rounded-lg transition-colors group">
            <i class="bx bx-support text-2xl text-gray-400 group-hover:text-purple-600 mb-1"></i>
            <span class="text-xs font-bold text-gray-600 dark:text-gray-300 group-hover:text-purple-600">Ticket</span>
        </a>
        <a href="{{ route('vouchers.index') }}" class="flex flex-col items-center justify-center p-3.5 hover:bg-gray-50 dark:hover:bg-gray-900 rounded-lg transition-colors group">
            <i class="bx bx-barcode-reader text-2xl text-gray-400 group-hover:text-orange-600 mb-1"></i>
            <span class="text-xs font-bold text-gray-600 dark:text-gray-300 group-hover:text-orange-600">Vouchers</span>
        </a>
    </div>
</div>
