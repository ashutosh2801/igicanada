<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('igi_email_template') || Schema::hasColumn('igi_email_template', 'sales_channel')) {
            return;
        }

        Schema::table('igi_email_template', function (Blueprint $table): void {
            $table->string('sales_channel', 20)
                ->default('wholesale')
                ->index()
                ->after('id');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('igi_email_template') && Schema::hasColumn('igi_email_template', 'sales_channel')) {
            Schema::table('igi_email_template', function (Blueprint $table): void {
                $table->dropColumn('sales_channel');
            });
        }
    }
};
