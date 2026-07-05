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
    Schema::create('routers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->string('ip_address');
        $table->string('api_username');
        $table->string('api_password');
        $table->string('secret_key')->nullable();
        $table->json('port_configuration')->nullable(); // Needed for step 3 of ZTP
        
        // FIXED: Using a string instead of a strict enum prevents truncation errors
        $table->string('status')->default('pending'); 
        
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
   public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
