{{-- Expects $packageBreakdown/$packagePlanTypes/$currency in scope.
     "Sales" is left as "—" — the reference design shows the same placeholder dash there,
     since this app doesn't distinguish a separate "sales" metric from the subscription count. --}}
<div class="card h-100 rp-rise" style="--rp-delay: {{ $delay ?? 0 }}ms">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0">Packages</h3>
        <a href="{{ route('reports.analytics') }}" class="small fw-bold">Full Report</a>
    </div>

    @if($packageBreakdown->isEmpty())
        <div class="card-body text-center text-muted text-uppercase small py-5">No package sales this month.</div>
    @else
        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead>
                    <tr>
                        <th>Packages</th>
                        <th>Category</th>
                        <th>Subscription</th>
                        <th>Revenue</th>
                        <th>Sales</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($packageBreakdown as $row)
                        @php $oneispType = $packagePlanTypes[$row->package_name] ?? 'ppp'; @endphp
                        <tr>
                            <td class="fw-bold text-nowrap">{{ $row->package_name }}</td>
                            <td>
                                <span class="avatar avatar-sm bg-primary-lt">
                                    <i class="ti {{ $oneispType === 'hotspot' ? 'ti-broadcast' : 'ti-device-desktop' }}"></i>
                                </span>
                                <span class="text-muted small align-middle ms-1">{{ $oneispType }}</span>
                            </td>
                            <td>
                                <span class="fw-bold">{{ number_format($row->sales_count) }}</span>
                                <span class="d-block text-muted" style="font-size:.6875rem">in 1 month</span>
                            </td>
                            <td class="fw-bold text-nowrap">{{ $currency }} {{ number_format($row->revenue) }}</td>
                            <td class="text-muted">&mdash;</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
