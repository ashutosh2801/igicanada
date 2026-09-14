<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `products` MODIFY `visibility` ENUM('retail', 'wholesale', 'both') NOT NULL DEFAULT 'both'");
        DB::statement('ALTER TABLE `product_variants` MODIFY `is_available_retail` TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `products` MODIFY `visibility` ENUM('retail', 'wholesale', 'both') NOT NULL DEFAULT 'wholesale'");
        DB::statement('ALTER TABLE `product_variants` MODIFY `is_available_retail` TINYINT(1) NOT NULL DEFAULT 0');
    }
};
