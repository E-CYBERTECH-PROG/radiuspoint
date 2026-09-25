<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Turns balance_adjustments into the single audit trail for every manual recharge an admin
    // makes: wallet credits/debits (kind=balance) and manual expiry extensions (kind=extension),
    // which previously left no record of who extended whom, by how much, or when.
    public function up(): void
    {
        Schema::table('balance_adjustments', function (Blueprint $table) {
            $table->string('kind', 20)->default('balance')->after('pppoe_user_id')->index();
            $table->decimal('amount', 10, 2)->nullable()->change();
            $table->timestamp('previous_expires_at')->nullable()->after('amount');
            $table->timestamp('new_expires_at')->nullable()->after('previous_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('balance_adjustments', function (Blueprint $table) {
            $table->dropColumn(['kind', 'previous_expires_at', 'new_expires_at']);
            $table->decimal('amount', 10, 2)->nullable(false)->change();
        });
    }
};
