{{--
    Expects $user in scope (ProfileController::edit() already passes it). Unlike the color
    pickers above (client-only, localStorage), this submits immediately to the server — the
    dashboard's actual HTML structure differs per layout, so it has to be known before
    DashboardController renders, not toggled client-side after the fact.
--}}
@php
    $layouts = [
        'standard' => [
            'label' => 'Standard',
            'description' => 'Operations and analytics get equal billing.',
            'rows' => [[100], [65, 35], [65, 35], [50, 50]],
        ],
        'ops' => [
            'label' => 'Ops-First',
            'description' => 'Split stat tiles; expiring customers and activity come first.',
            'rows' => [[18, 18, 18, 18, 18], [100], [65, 35], [50, 50], [33, 33, 33]],
        ],
        'analytics' => [
            'label' => 'Analytics-First',
            'description' => 'Revenue and growth charts come first.',
            'rows' => [[100], [65, 35], [50, 50], [65, 35]],
        ],
    ];
    $current = in_array($user->dashboard_layout, \App\Models\User::DASHBOARD_LAYOUTS, true) ? $user->dashboard_layout : 'standard';
@endphp

<div>
    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Dashboard Layout</h3>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Which arrangement you see when you open the Dashboard.</p>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        @foreach($layouts as $key => $l)
            <form action="{{ route('profile.dashboard-layout') }}" method="POST">
                @csrf
                @method('PATCH')
                <input type="hidden" name="dashboard_layout" value="{{ $key }}">
                <button type="submit" class="w-full text-left border rounded-xl p-3 transition-colors {{ $current === $key ? 'border-blue-500 ring-2 ring-blue-500/20 bg-blue-50/50 dark:bg-blue-900/10' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                    <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-1.5 space-y-1 mb-2.5" aria-hidden="true">
                        @foreach($l['rows'] as $row)
                            <div class="flex gap-1 h-2.5">
                                @foreach($row as $width)
                                    <div class="rounded-sm {{ $current === $key ? 'bg-blue-400 dark:bg-blue-600' : 'bg-gray-300 dark:bg-gray-700' }}" style="width: {{ $width }}%"></div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $l['label'] }}</span>
                        @if($current === $key)
                            <i class="bx bxs-check-circle text-blue-500 text-sm"></i>
                        @endif
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $l['description'] }}</p>
                </button>
            </form>
        @endforeach
    </div>
</div>
