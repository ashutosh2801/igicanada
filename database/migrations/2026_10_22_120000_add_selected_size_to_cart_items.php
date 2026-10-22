<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('cart_items', 'selected_size')) {
                $table->string('selected_size')->nullable()->after('product_variant_id');
            }

            // MySQL may be using the old unique index to satisfy the cart_id
            // foreign key because it is the only index beginning with cart_id.
            $table->index('cart_id');
            $table->dropUnique('cart_items_cart_id_product_variant_id_unique');
            $table->unique(['cart_id', 'product_variant_id', 'selected_size']);
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table): void {
            $table->dropUnique('cart_items_cart_id_product_variant_id_selected_size_unique');
            $table->dropColumn('selected_size');
            $table->unique(['cart_id', 'product_variant_id']);
        });
    }
};
