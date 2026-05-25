<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->time('patrol_1_time')->nullable()->after('checkout_time');
            $table->time('standby_1_time')->nullable()->after('patrol_1_time');
            $table->time('patrol_2_time')->nullable()->after('standby_1_time');
            $table->time('standby_2_time')->nullable()->after('patrol_2_time');
        });

        DB::table('schedules')->update([
            'patrol_1_time' => DB::raw('COALESCE(patrol_1_a_time, patrol_a_time)'),
            'standby_1_time' => DB::raw('COALESCE(patrol_1_b_time, patrol_b_time)'),
            'patrol_2_time' => DB::raw('COALESCE(patrol_2_a_time, patrol_c_time)'),
            'standby_2_time' => DB::raw('COALESCE(patrol_2_b_time, patrol_2_c_time)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn([
                'patrol_1_time',
                'standby_1_time',
                'patrol_2_time',
                'standby_2_time',
            ]);
        });
    }
};
