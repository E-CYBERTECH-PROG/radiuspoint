<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        // Links this transaction securely to the specific ISP (Tenant)
        $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
        $table->string('customer_name');
        $table->string('phone_number');
        $table->string('package_name');
        $table->decimal('amount', 10, 2);
        $table->string('payment_method')->default('M-Pesa STK');
        $table->enum('status', ['success', 'failed'])->default('success');
        $table->string('mpesa_receipt')->unique()->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
