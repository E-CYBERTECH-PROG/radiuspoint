<x-sidebar-layout title="Deploy Router">
    <div class="py-2 px-0">
        <div class="max-w-5xl mx-auto">

            <div class="mb-8 flex items-center justify-between">
                <div>
                    <a href="{{ route('routers.index') }}" class="text-sm font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors flex items-center gap-2 mb-2">
                        <i class="bx bx-left-arrow-alt text-lg"></i> Back to Hardware &amp; Routers
                    </a>
                    <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Deploy Hardware</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">Connect a new MikroTik router automatically.</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-950 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col lg:flex-row transition-colors duration-300">

                <div class="bg-gray-50 dark:bg-gray-900/60 p-8 lg:w-1/3 border-b lg:border-b-0 lg:border-r border-gray-200 dark:border-gray-800">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-xl">
                            <i class='bx bx-bot text-2xl'></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Auto-Provisioning</h3>
                    </div>
                    <ul class="space-y-5 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start gap-3">
                            <i class='bx bxs-magic-wand text-indigo-500 mt-0.5 text-lg'></i>
                            <span><strong>IP:</strong> Allocated automatically.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class='bx bx-shield-quarter text-green-500 mt-0.5 text-lg'></i>
                            <span><strong>Credentials:</strong> Generated automatically.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class='bx bx-terminal text-gray-700 dark:text-gray-300 mt-0.5 text-lg'></i>
                            <span><strong>Setup:</strong> One script to paste into the terminal.</span>
                        </li>
                    </ul>
                </div>

                <div class="p-8 lg:w-2/3 flex flex-col justify-center">
                    <form action="{{ route('routers.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Working Label</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class='bx bx-map text-gray-400 text-lg'></i>
                                </div>
                                <input type="text" name="name" required placeholder="e.g., Kileleshwa Base Station"
                                       class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl pl-11 pr-4 py-4 text-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm outline-none">
                            </div>
                            <p class="mt-3 text-xs text-gray-500">A temporary name — replaced automatically once the router connects.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">RouterOS Version</label>
                            <select name="routeros_version" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-4 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm outline-none cursor-pointer">
                                <option value="v7">v7 and above</option>
                                <option value="v6">v6.48.5 and above</option>
                            </select>
                            <p class="mt-3 text-xs text-gray-500">The provisioning script's tunnel setup differs between RouterOS versions.</p>
                        </div>

                        <div class="pt-8 mt-6 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-5">
                            <a href="{{ route('routers.index') }}" class="px-4 py-2 text-sm font-bold text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 px-8 rounded-xl shadow-sm transition-all transform hover:-translate-y-0.5 flex items-center gap-2 group">
                                Generate Script <i class="bx bx-right-arrow-alt text-xl group-hover:translate-x-1 transition-transform"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-sidebar-layout>
