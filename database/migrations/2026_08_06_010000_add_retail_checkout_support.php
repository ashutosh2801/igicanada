<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('sales_channel', 20)->default('wholesale')->index()->after('user_id');
            $table->string('session_key', 64)->nullable()->unique()->after('sales_channel');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('customer_email')->nullable()->index()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('customer_email');
        });

        Schema::table('carts', function (Blueprint $table): void {
            $table->dropColumn(['sales_channel', 'session_key']);
        });
    }
};
