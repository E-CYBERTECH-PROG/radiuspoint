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
        Schema::create('captive_portals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            // One portal config per router; a router with no row gets the "minimal" default.
            $table->foreignId('router_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('template')->default('minimal');
            $table->string('logo_url')->nullable();
            $table->string('primary_color')->default('#2563eb');
            $table->string('notice_title')->nullable();
            $table->text('notice_body')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('captive_portals');
    }
};
