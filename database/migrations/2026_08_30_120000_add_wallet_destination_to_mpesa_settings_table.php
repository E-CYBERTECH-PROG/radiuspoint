<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a gateway row describe where the money should land (paybill + account number, or a
 * bank's own paybill + the tenant's bank account number) separately from the Daraja app that
 * authenticates the STK push — see MpesaService::for(). A 'till' destination still just uses
 * the existing `shortcode` column; only paybill's account reference and bank's two numbers are
 * new here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mpesa_settings', function (Blueprint $table) {
            $table->string('account_number')->nullable()->after('shortcode');
            $table->string('bank_paybill')->nullable()->after('account_number');
            $table->string('bank_account_number')->nullable()->after('bank_paybill');
        });
    }

    public function down(): void
    {
        Schema::table('mpesa_settings', function (Blueprint $table) {
            $table->dropColumn(['account_number', 'bank_paybill', 'bank_account_number']);
        });
    }
};
