<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->resolveChannelColumn();

        if (! DB::table('standard_shipping_rates')->where('sales_channel', 'walletsandbelts')->exists()) {
            $now = now();
            $rows = DB::table('standard_shipping_rates')
                ->where('sales_channel', 'retail')
                ->get()
                ->map(fn (object $rate): array => [
                    'sales_channel' => 'walletsandbelts',
                    'country' => $rate->country,
                    'name' => $rate->name,
                    'min_order_amount' => $rate->min_order_amount,
                    'max_order_amount' => $rate->max_order_amount,
                    'charge' => $rate->charge,
                    'is_active' => $rate->is_active,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            DB::table('standard_shipping_rates')->insert($rows);
        }
    }

    public function down(): void
    {
        DB::table('standard_shipping_rates')->where('sales_channel', 'walletsandbelts')->delete();

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE standard_shipping_rates MODIFY COLUMN sales_channel ENUM('wholesale', 'retail') NOT NULL DEFAULT 'wholesale'");
        } else {
            $this->rebuildForSqlite(['wholesale', 'retail']);
        }
    }

    private function resolveChannelColumn(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE standard_shipping_rates MODIFY COLUMN sales_channel ENUM('wholesale', 'retail', 'walletsandbelts') NOT NULL DEFAULT 'wholesale'");

            return;
        }

        $this->rebuildForSqlite(['wholesale', 'retail', 'walletsandbelts']);
    }

    /**
     * SQLite stores enums as a varchar with a check constraint, so the column
     * must be rebuilt to accept the extra channel value.
     *
     * @param  array<int, string>  $values
     */
    private function rebuildForSqlite(array $values): void
    {
        $checks = collect($values)->map(fn (string $value): string => "'".$value."'")->implode(', ');

        $this->dropSqliteIndexes();
        DB::statement('ALTER TABLE standard_shipping_rates RENAME TO standard_shipping_rates_legacy');
        DB::statement("CREATE TABLE standard_shipping_rates (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            sales_channel VARCHAR NOT NULL DEFAULT 'wholesale' CHECK (\"sales_channel\" IN ({$checks})),
            country VARCHAR NOT NULL CHECK (\"country\" IN ('CA','US')),
            name VARCHAR(100) NOT NULL,
            min_order_amount NUMERIC NOT NULL,
            max_order_amount NUMERIC NOT NULL,
            charge NUMERIC NOT NULL,
            is_active TINYINT NOT NULL DEFAULT 1,
            created_at DATETIME,
            updated_at DATETIME
        )");
        DB::statement('INSERT INTO standard_shipping_rates (id, sales_channel, country, name, min_order_amount, max_order_amount, charge, is_active, created_at, updated_at)
            SELECT id, sales_channel, country, name, min_order_amount, max_order_amount, charge, is_active, created_at, updated_at
            FROM standard_shipping_rates_legacy');
        DB::statement('DROP TABLE standard_shipping_rates_legacy');
        $this->restoreIndexes();
    }

    private function dropSqliteIndexes(): void
    {
        DB::statement('DROP INDEX IF EXISTS shipping_channel_country_min_unique');
        DB::statement('DROP INDEX IF EXISTS standard_shipping_rates_sales_channel_index');
        DB::statement('DROP INDEX IF EXISTS standard_shipping_rates_country_is_active_min_order_amount_index');
    }

    private function restoreIndexes(): void
    {
        DB::statement('CREATE INDEX standard_shipping_rates_sales_channel_index ON standard_shipping_rates (sales_channel)');
        DB::statement('CREATE INDEX standard_shipping_rates_country_is_active_min_order_amount_index ON standard_shipping_rates (country, is_active, min_order_amount)');
        DB::statement('CREATE UNIQUE INDEX shipping_channel_country_min_unique ON standard_shipping_rates (sales_channel, country, min_order_amount)');
    }
};