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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('item_tag_code', 50)->unique();
            $table->string('shoe_brand_model', 100);
            $table->string('shoe_material', 50);
            $table->string('shoe_color', 50)->nullable();
            $table->text('defect_notes')->nullable();
            $table->string('storage_shelf_number', 20)->nullable();
            $table->enum('status', [
                'WAITING_PICKUP',
                'CHECKED_IN',
                'WASHING',
                'DRYING',
                'QC_INSPECTION',
                'READY_DELIVERY',
                'DELIVERED',
                'CANCELLED',
            ])->default('WAITING_PICKUP')->index();
            $table->timestamp('estimated_completion')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
