{{-- Expects $packageBreakdown/$packagePlanTypes/$currency in scope.
     "Sales" is left as "—" — the reference design shows the same placeholder dash there,
     since this app doesn't distinguish a separate "sales" metric from the subscription count. --}}
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm h-full flex flex-col rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Packages</h3>
        <a href="{{ route('reports.analytics') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Full Report</a>
    </div>

    @if($packageBreakdown->isEmpty())
        <p class="text-center text-gray-400 text-xs tracking-widest uppercase py-16">No package sales this month.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                        <th class="text-left px-5 py-2.5">Packages</th>
                        <th class="text-left px-5 py-2.5">Category</th>
                        <th class="text-left px-5 py-2.5">Subscription</th>
                        <th class="text-left px-5 py-2.5">Revenue</th>
                        <th class="text-left px-5 py-2.5">Sales</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-900">
                    @foreach($packageBreakdown as $row)
                        @php $oneispType = $packagePlanTypes[$row->package_name] ?? 'ppp'; @endphp
                        <tr>
                            <td class="px-5 py-3 font-bold text-gray-900 dark:text-white whitespace-nowrap">{{ $row->package_name }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400">
                                    <i class="bx {{ $oneispType === 'hotspot' ? 'bx-broadcast' : 'bx-desktop' }} text-base"></i>
                                </span>
                                <span class="ml-1.5 text-xs text-gray-400 align-middle">{{ $oneispType }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="font-bold text-gray-900 dark:text-white">{{ number_format($row->sales_count) }}</span>
                                <span class="block text-[11px] text-gray-400">in 1 month</span>
                            </td>
                            <td class="px-5 py-3 font-bold text-gray-900 dark:text-white whitespace-nowrap">{{ $currency }} {{ number_format($row->revenue) }}</td>
                            <td class="px-5 py-3 text-gray-300 dark:text-gray-600">&mdash;</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
