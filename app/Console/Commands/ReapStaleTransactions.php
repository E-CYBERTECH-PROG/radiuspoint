<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;

/**
 * If the request handling an STK push never finishes (killed worker, crashed process), the
 * Transaction stays 'pending' forever. An M-Pesa STK prompt expires after ~90s, so anything
 * still pending well past that is dead.
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
