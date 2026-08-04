<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_settings', function (Blueprint $table): void {
            $table->boolean('show_new_arrivals')->default(true);
            $table->string('new_arrivals_eyebrow')->nullable()->default('Fresh for your store');
            $table->string('new_arrivals_title')->default('New arrivals');
            $table->text('new_arrivals_description')->nullable()->default('Discover the latest wholesale products added to IGI Canada.');
            $table->unsignedTinyInteger('new_arrivals_count')->default(8);
        });
    }

    public function down(): void
    {
        Schema::table('homepage_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'show_new_arrivals',
                'new_arrivals_eyebrow',
                'new_arrivals_title',
                'new_arrivals_description',
                'new_arrivals_count',
            ]);
        });
    }
};
