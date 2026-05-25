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
        Schema::create('qr_sets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('token_prefix')->unique();
            $table->boolean('is_active')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('qr_set_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_set_id')->constrained('qr_sets')->cascadeOnDelete();
            $table->string('point_type');
            $table->string('token')->unique();
            $table->timestamps();

            $table->unique(['qr_set_id', 'point_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_set_points');
        Schema::dropIfExists('qr_sets');
    }
};
