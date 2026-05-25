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
        Schema::table('schedules', function (Blueprint $table) {
            $table->time('patrol_1_a_time')->nullable()->after('checkout_time');
            $table->time('patrol_1_b_time')->nullable()->after('patrol_1_a_time');
            $table->time('patrol_1_c_time')->nullable()->after('patrol_1_b_time');
            $table->time('patrol_2_a_time')->nullable()->after('patrol_1_c_time');
            $table->time('patrol_2_b_time')->nullable()->after('patrol_2_a_time');
            $table->time('patrol_2_c_time')->nullable()->after('patrol_2_b_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn([
                'patrol_1_a_time',
                'patrol_1_b_time',
                'patrol_1_c_time',
                'patrol_2_a_time',
                'patrol_2_b_time',
                'patrol_2_c_time',
            ]);
        });
    }
};
