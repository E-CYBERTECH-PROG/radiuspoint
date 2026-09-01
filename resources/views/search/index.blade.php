<x-sidebar-layout title="Search">
    <div class="mb-4">
        <form action="{{ route('search') }}" method="GET" class="input-icon" style="max-width:42rem">
            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
            <input type="text" name="q" value="{{ $q }}" autofocus placeholder="Search phone numbers, routers, transactions…" class="form-control">
        </form>
    </div>

    @if($q === '')
        <p class="text-muted">Type something above to search.</p>
    @else
        @php $total = $results['hotspot_users']->count() + $results['pppoe_users']->count() + $results['routers']->count() + $results['transactions']->count(); @endphp

        @if($total === 0)
            <p class="text-muted">No results for "{{ $q }}".</p>
        @endif

        @if($results['hotspot_users']->isNotEmpty())
            <div class="mb-4">
                <h3 class="text-uppercase text-muted small fw-bold mb-2">Hotspot Users</h3>
                <div class="list-group">
                    @foreach($results['hotspot_users'] as $user)
                        <a href="{{ route('hotspot-users.show', $user) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span class="fw-bold">{{ $user->phone_number }}</span>
                            <span class="text-muted text-uppercase small">{{ $user->status }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if($results['pppoe_users']->isNotEmpty())
            <div class="mb-4">
                <h3 class="text-uppercase text-muted small fw-bold mb-2">PPPoE Users</h3>
                <div class="list-group">
                    @foreach($results['pppoe_users'] as $user)
                        <a href="{{ route('pppoe-users.show', $user) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span class="fw-bold">{{ $user->username }}</span>
                            <span class="text-muted text-uppercase small">{{ $user->status }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if($results['routers']->isNotEmpty())
            <div class="mb-4">
                <h3 class="text-uppercase text-muted small fw-bold mb-2">Routers</h3>
                <div class="list-group">
                    @foreach($results['routers'] as $router)
                        <a href="{{ route('routers.show', $router) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span class="fw-bold">{{ $router->name }}</span>
                            <span class="text-muted small">{{ $router->ip_address }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if($results['transactions']->isNotEmpty())
            <div class="mb-4">
                <h3 class="text-uppercase text-muted small fw-bold mb-2">Transactions</h3>
                <div class="list-group">
                    @foreach($results['transactions'] as $transaction)
                        <a href="{{ route('transactions.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold">{{ $transaction->customer_name }}</span>
                                <span class="text-muted small ms-2">{{ $transaction->phone_number }}</span>
                            </div>
                            <span class="font-monospace fw-bold small">KES {{ number_format($transaction->amount) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</x-sidebar-layout>
