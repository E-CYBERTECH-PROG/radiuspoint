<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // GPON/EPON OLT vendors this integration targets — both are Telnet/SSH CLI driven
            // (Cisco IOS-style config mode), not a structured binary API like MikroTik's, so the
            // integration is a raw remote-terminal console first (OltSshService) rather than
            // parsed ONU-list/provisioning screens, until we can verify exact CLI command syntax
            // and output format against a real unit of each brand.
            $table->enum('brand', ['vsol', 'hioso', 'other'])->default('other');
            $table->string('ip_address');
            $table->unsignedInteger('ssh_port')->default(22);
            $table->string('username');
            $table->string('password');
            $table->string('status')->default('pending');
            $table->timestamp('last_seen')->nullable();
            $table->unsignedInteger('pon_ports')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('olt_terminal_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->text('command');
            $table->longText('result')->nullable();
            $table->boolean('success')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olt_terminal_logs');
        Schema::dropIfExists('olts');
    }
};
