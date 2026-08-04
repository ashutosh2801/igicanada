<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_address_id')->nullable()->constrained()->nullOnDelete();
            $table->json('shipping_address');
            $table->string('currency', 3)->default('CAD');
            $table->string('status')->default('awaiting_quote')->index();
            $table->string('payment_status')->default('unpaid')->index();
            $table->string('payment_method')->default('invoice');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->text('customer_notes')->nullable();
            $table->timestamp('placed_at');
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->unsignedBigInteger('legacy_product_id')->nullable();
            $table->unsignedBigInteger('legacy_variant_id')->nullable();
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->string('option')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
