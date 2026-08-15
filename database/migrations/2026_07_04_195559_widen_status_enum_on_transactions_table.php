<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('status')->default('success')->change();
            });
        } else {
            DB::statement("ALTER TABLE transactions MODIFY status ENUM('pending', 'success', 'failed') DEFAULT 'success'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('status')->default('success')->change();
            });
        } else {
            DB::statement("ALTER TABLE transactions MODIFY status ENUM('success', 'failed') DEFAULT 'success'");
        }
    }
};
