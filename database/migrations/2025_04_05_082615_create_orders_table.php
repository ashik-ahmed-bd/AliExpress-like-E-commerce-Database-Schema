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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->string('status', 50)->default('pending');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('shipping_amount', 6, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->string('payment_method', 100);
            $table->string('payment_status', 50)->default('pending');
            $table->text('shipping_address');
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
