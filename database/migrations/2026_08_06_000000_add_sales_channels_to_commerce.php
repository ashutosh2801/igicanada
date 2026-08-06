<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('retail_name')->nullable()->after('name');
            $table->longText('retail_description')->nullable()->after('description');
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->decimal('retail_compare_at_price', 12, 2)->nullable()->after('retail_price');
            $table->boolean('is_available_wholesale')->default(true)->index()->after('stock_quantity');
            $table->boolean('is_available_retail')->default(false)->index()->after('is_available_wholesale');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('sales_channel', 20)->default('wholesale')->index()->after('order_number');
        });

        Schema::table('contact_enquiries', function (Blueprint $table): void {
            $table->string('sales_channel', 20)->default('wholesale')->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('contact_enquiries', function (Blueprint $table): void {
            $table->dropColumn('sales_channel');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('sales_channel');
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropColumn(['retail_compare_at_price', 'is_available_wholesale', 'is_available_retail']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['retail_name', 'retail_description']);
        });
    }
};
