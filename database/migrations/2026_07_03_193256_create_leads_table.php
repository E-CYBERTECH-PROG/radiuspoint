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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('location')->nullable();
            $table->string('notes', 200)->nullable();
            $table->enum('status', ['new', 'contacted', 'converted', 'lost'])->default('new');
            $table->foreignId('converted_to_pppoe_user_id')->nullable()->constrained('pppoe_users')->nullOnDelete();
            $table->foreignId('converted_to_hotspot_user_id')->nullable()->constrained('hotspot_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
