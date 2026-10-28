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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 30)->unique()->index();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2);
            $table->enum('payment_status', ['UNPAID', 'PAID', 'REFUNDED'])->default('UNPAID');
            $table->enum('payment_method', ['COD', 'QRIS', 'BANK_TRANSFER', 'SPATOE_PASS'])->default('COD');
            $table->text('pickup_address');
            $table->text('delivery_address');
            $table->timestamp('pickup_time')->nullable();
            $table->timestamp('delivered_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
