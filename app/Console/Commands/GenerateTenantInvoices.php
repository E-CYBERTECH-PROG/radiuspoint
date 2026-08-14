<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantInvoice;
use App\Models\Transaction;
use Illuminate\Console\Command;

class GenerateTenantInvoices extends Command
{
    protected $signature = 'billing:generate-invoices';

    protected $description = 'Issues each eligible tenant a commission invoice (3% of last calendar month\'s successful transaction revenue), due after a 2-day grace period';

    public function handle(): int
    {
        $periodStart = now()->subMonthNoOverflow()->startOfMonth();
        $periodEnd = now()->subMonthNoOverflow()->endOfMonth();

        $issued = 0;

        // Runs outside any authenticated tenant context.
        foreach (Tenant::withoutGlobalScope('tenant')->where('status', 'active')->get() as $tenant) {
            // Tenants with no subscription expiry are grandfathered in until a platform admin
            // sets their terms. Only bill tenants whose trial ended before this period started.
            if (! $tenant->subscription_expires_at || $tenant->subscription_expires_at->gt($periodStart)) {
                continue;
            }

            // Skip periods already invoiced (also enforced by a unique DB constraint).
            $alreadyInvoiced = TenantInvoice::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('period_start', $periodStart->toDateString())
                ->exists();

            if ($alreadyInvoiced) {
                continue;
            }

            $revenue = (float) Transaction::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenant->id)
                ->where('status', 'success')
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->sum('amount');

            // Skip zero-revenue tenants rather than issue a KES 0 invoice.
            if ($revenue <= 0) {
                continue;
            }

            $rate = config('billing.commission_rate');

            TenantInvoice::withoutGlobalScope('tenant')->create([
                'tenant_id' => $tenant->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'revenue_total' => $revenue,
                'commission_rate' => $rate,
                'amount_due' => round($revenue * $rate, 2),
                'status' => 'pending',
                'due_at' => now()->addDays(config('billing.grace_days')),
            ]);

            $issued++;
        }

        $this->info("Issued {$issued} commission invoice(s) for {$periodStart->format('F Y')}.");

        return self::SUCCESS;
    }
}
