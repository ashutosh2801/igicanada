<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropUnique(['legacy_id']);
            $table->index('legacy_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_shipping_id')->nullable()->after('legacy_id');
            $table->text('legacy_order_ids')->nullable()->after('legacy_shipping_id');
            $table->string('shipping_method')->nullable()->after('payment_method');
            $table->string('shipping_service')->nullable()->after('shipping_method');
            $table->string('payment_reference')->nullable()->after('shipping_service');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'legacy_shipping_id',
                'legacy_order_ids',
                'shipping_method',
                'shipping_service',
                'payment_reference',
            ]);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['legacy_id']);
            $table->unique('legacy_id');
        });
    }
};
