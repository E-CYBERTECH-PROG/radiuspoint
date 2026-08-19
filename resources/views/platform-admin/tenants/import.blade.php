<x-sidebar-layout title="Import Tenants">
    <div class="mb-6">
        <a href="{{ route('platform-admin.tenants.index') }}" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors inline-flex items-center gap-2 mb-2">
            <i class="bx bx-left-arrow-alt text-lg"></i> Back to Tenants
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Import Tenants</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">From a CSV file — each new owner gets a temporary password by email.</p>
    </div>

    <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-8 max-w-2xl space-y-6">
        <a href="{{ route('platform-admin.tenants.import-template') }}" class="inline-flex items-center gap-2 text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
            <i class="bx bx-download text-lg"></i> Download CSV Template
        </a>

        <form action="{{ route('platform-admin.tenants.import') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">CSV File <span class="text-red-500">*</span></label>
                <input type="file" name="file" accept=".csv,.txt" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white py-3 px-4 rounded-lg outline-none">
                @error('file') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-sm transition-colors inline-flex items-center gap-2">
                <i class="bx bx-upload text-lg"></i> Import
            </button>
        </form>
    </div>

    @if(session('importResults'))
        @php($results = session('importResults'))
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl">
            <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6">
                <h3 class="text-md font-bold text-green-600 dark:text-green-400 mb-4">Created ({{ count($results['created']) }})</h3>
                <div class="space-y-2 text-sm">
                    @forelse($results['created'] as $row)
                        <p class="text-gray-700 dark:text-gray-300">Row {{ $row['row'] }}: <span class="font-bold">{{ $row['company_name'] }}</span> &middot; {{ $row['owner_email'] }}</p>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400">None.</p>
                    @endforelse
                </div>
            </div>
            <div class="bg-white dark:bg-gray-950 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm p-6">
                <h3 class="text-md font-bold text-red-600 dark:text-red-400 mb-4">Failed ({{ count($results['failed']) }})</h3>
                <div class="space-y-2 text-sm">
                    @forelse($results['failed'] as $row)
                        <p class="text-gray-700 dark:text-gray-300">Row {{ $row['row'] }}: <span class="text-red-500">{{ $row['reason'] }}</span></p>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400">None.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</x-sidebar-layout>
