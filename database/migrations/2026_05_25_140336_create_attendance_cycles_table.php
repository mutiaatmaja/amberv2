<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('cycle_date')->index();
            $table->dateTime('started_at');
            $table->dateTime('expected_end_at');
            $table->dateTime('ended_at')->nullable();
            $table->string('status')->default('open')->index();
            $table->string('checkout_mode')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'cycle_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_cycles');
    }
};
