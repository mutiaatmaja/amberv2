<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->foreignId('attendance_cycle_id')
                ->nullable()
                ->after('id')
                ->constrained('attendance_cycles')
                ->nullOnDelete();
            $table->string('window_group')->nullable()->after('qr_set_point_id');

            $table->index(['attendance_cycle_id', 'point_type']);
            $table->index(['user_id', 'window_group']);
        });
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropIndex(['attendance_cycle_id', 'point_type']);
            $table->dropIndex(['user_id', 'window_group']);
            $table->dropConstrainedForeignId('attendance_cycle_id');
            $table->dropColumn('window_group');
        });
    }
};
