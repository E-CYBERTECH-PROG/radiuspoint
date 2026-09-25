<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A manual credit/debit an admin applies to a registered (non-voucher) customer's prepaid
 * balance — deliberately its own table rather than a row in `transactions`, so it can never
 * leak into the revenue figures (income_today/income_month/etc.) that sum `transactions` by
 * status='success' in half a dozen places across DashboardController/ReportController. A
 * wallet correction isn't a sale, and keeping it structurally separate means no report query
 * has to remember to exclude it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('balance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hotspot_user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('pppoe_user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_adjustments');
    }
};
