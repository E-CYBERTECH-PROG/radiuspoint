<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;

/**
 * A Transaction is created as 'pending' before the STK push is attempted, then updated to
 * 'success'/'failed' in the same request. If that request never finishes (a killed PHP-FPM
 * worker, a crashed process — confirmed happening on this server: workers SIGKILLed after
 * 48-140s under load), the row is orphaned at 'pending' forever with nothing to ever revisit it.
 * A real M-Pesa STK prompt expires within ~90s if unanswered, so anything still pending well
 * past that is dead, not "still waiting."
 */
class ReapStaleTransactions extends Command
{
    protected $signature = 'transactions:reap-stale';

    protected $description = 'Marks Transactions stuck at pending long past a normal M-Pesa STK timeout as failed';

    public function handle(): int
    {
        $reaped = Transaction::withoutGlobalScope('tenant')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(10))
            ->update(['status' => 'failed']);

        $this->info("Reaped {$reaped} stale pending transaction(s).");

        return self::SUCCESS;
    }
}
