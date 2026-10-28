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
        Schema::create('order_item_qc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->unique()->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('inspected_by')->constrained('users')->restrictOnDelete();
            $table->boolean('is_upper_clean')->default(false);
            $table->boolean('is_midsole_clean')->default(false);
            $table->boolean('is_outsole_clean')->default(false);
            $table->boolean('is_completely_dry')->default(false);
            $table->boolean('is_perfumed')->default(false);
            $table->boolean('is_silica_packed')->default(false);
            $table->boolean('qc_passed')->default(false);
            $table->text('qc_notes')->nullable();
            $table->timestamp('inspected_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_qc');
    }
};
