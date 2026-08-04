<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_settings', function (Blueprint $table): void {
            $table->json('hero_image_paths')->nullable()->after('hero_image_path');
            $table->unsignedTinyInteger('hero_slider_interval')->default(5)->after('hero_image_paths');
        });

        DB::table('homepage_settings')
            ->whereNotNull('hero_image_path')
            ->orderBy('id')
            ->eachById(function (object $settings): void {
                DB::table('homepage_settings')->where('id', $settings->id)->update([
                    'hero_image_paths' => json_encode([$settings->hero_image_path]),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('homepage_settings', function (Blueprint $table): void {
            $table->dropColumn(['hero_image_paths', 'hero_slider_interval']);
        });
    }
};
