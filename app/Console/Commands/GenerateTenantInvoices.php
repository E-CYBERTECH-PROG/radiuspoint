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

        // withoutGlobalScope: this runs outside any authenticated tenant context.
        foreach (Tenant::withoutGlobalScope('tenant')->where('status', 'active')->get() as $tenant) {
            // Tenants with no expiry set were never enrolled in the trial-then-billing flow —
            // pre-existing accounts from before this feature shipped, or bulk-imported ISPs.
            // Grandfathered in until a platform admin explicitly sets their subscription terms
            // (via the tenant edit form), rather than surprise-billing accounts that never
            // agreed to this. Only tenants whose trial had already fully ended before this
            // billing period started are eligible.
            if (! $tenant->subscription_expires_at || $tenant->subscription_expires_at->gt($periodStart)) {
                continue;
            }

            // Idempotent: re-running this command (e.g. after a fixed bug) never double-bills
            // a period that's already invoiced, thanks to the unique(tenant_id, period_start,
            // period_end) constraint — this check just avoids a needless query exception.
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

            // No revenue, no commission owed — skip rather than issue a KES 0 invoice that
            // would otherwise sit "pending" forever with nothing to actually pay.
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
