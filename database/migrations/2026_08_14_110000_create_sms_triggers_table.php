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
        Schema::create('sms_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            // One of App\Models\SmsTrigger::KEYS, a fixed catalog.
            $table->string('trigger_key');
            $table->foreignId('sms_template_id')->nullable()->constrained()->nullOnDelete();
            // Off by default; enabling is an explicit opt-in per tenant.
            $table->boolean('enabled')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'trigger_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_triggers');
    }
};
