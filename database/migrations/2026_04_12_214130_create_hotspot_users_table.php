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
        Schema::create('hotspot_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number');
            $table->string('mac_address')->nullable();
            $table->foreignId('current_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('current_router_id')->nullable()->constrained('routers')->nullOnDelete();
            $table->enum('status', ['active', 'expired', 'offline'])->default('offline');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotspot_users');
    }
};
