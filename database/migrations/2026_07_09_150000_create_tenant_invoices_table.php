<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('revenue_total', 12, 2);
            $table->decimal('commission_rate', 5, 4)->default(0.03);
            $table->decimal('amount_due', 12, 2);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            // Grace-period deadline — set at generation time (now + grace days) rather than
            // computed on the fly, so a later change to the grace-period length doesn't
            // retroactively alter already-issued invoices.
            $table->timestamp('due_at');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('marked_paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();

            // One invoice per tenant per billing period — the generator command relies on this
            // for idempotency (safe to re-run without duplicating an already-issued invoice).
            $table->unique(['tenant_id', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_invoices');
    }
};
