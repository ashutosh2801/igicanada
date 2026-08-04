<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('color_code', 7)->nullable()->after('color');
            $table->json('sizes')->nullable()->after('size');
        });

        DB::table('product_variants')
            ->whereNotNull('size')
            ->where('size', '!=', '')
            ->orderBy('id')
            ->eachById(function (object $variant): void {
                DB::table('product_variants')
                    ->where('id', $variant->id)
                    ->update(['sizes' => json_encode([trim((string) $variant->size)])]);
            });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['color_code', 'sizes']);
        });
    }
};
