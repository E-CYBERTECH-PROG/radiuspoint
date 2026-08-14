<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('captive_portals', function (Blueprint $table) {
            // Optional tenant-entered testimonials, nullable with no seeded defaults.
            $table->string('testimonial_1_text')->nullable()->after('notice_body');
            $table->string('testimonial_1_author')->nullable()->after('testimonial_1_text');
            $table->string('testimonial_2_text')->nullable()->after('testimonial_1_author');
            $table->string('testimonial_2_author')->nullable()->after('testimonial_2_text');
        });
    }

    public function down(): void
    {
        Schema::table('captive_portals', function (Blueprint $table) {
            $table->dropColumn(['testimonial_1_text', 'testimonial_1_author', 'testimonial_2_text', 'testimonial_2_author']);
        });
    }
};
