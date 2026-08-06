<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('categories', 'visibility')) {
            Schema::table('categories', function (Blueprint $table): void {
                $table->enum('visibility', ['wholesale', 'retail', 'both'])->default('both')->index()->after('description');
            });
        }

        if (! Schema::hasColumn('navigation_items', 'sales_channel')) {
            Schema::table('navigation_items', function (Blueprint $table): void {
                $table->enum('sales_channel', ['wholesale', 'retail'])->default('wholesale')->index()->after('id');
            });
        }

        if (! Schema::hasColumn('standard_shipping_rates', 'sales_channel')) {
            Schema::table('standard_shipping_rates', function (Blueprint $table): void {
                $table->dropUnique(['country', 'min_order_amount']);
                $table->enum('sales_channel', ['wholesale', 'retail'])->default('wholesale')->index()->after('id');
            });
        }

        Schema::table('standard_shipping_rates', function (Blueprint $table): void {
            $table->unique(['sales_channel', 'country', 'min_order_amount'], 'shipping_channel_country_min_unique');
        });

        $now = now();
        $retailRates = DB::table('standard_shipping_rates')
            ->where('sales_channel', 'wholesale')
            ->get()
            ->map(fn ($rate): array => [
                'sales_channel' => 'retail',
                'country' => $rate->country,
                'name' => $rate->name,
                'min_order_amount' => $rate->min_order_amount,
                'max_order_amount' => $rate->max_order_amount,
                'charge' => $rate->charge,
                'is_active' => $rate->is_active,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

        DB::table('standard_shipping_rates')->insert($retailRates);
    }

    public function down(): void
    {
        DB::table('standard_shipping_rates')->where('sales_channel', 'retail')->delete();

        Schema::table('standard_shipping_rates', function (Blueprint $table): void {
            $table->dropUnique('shipping_channel_country_min_unique');
            $table->dropColumn('sales_channel');
            $table->unique(['country', 'min_order_amount']);
        });

        Schema::table('navigation_items', fn (Blueprint $table) => $table->dropColumn('sales_channel'));
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('visibility'));
    }
};
