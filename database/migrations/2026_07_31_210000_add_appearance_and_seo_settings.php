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
            $table->string('favicon_path')->nullable()->after('logo_alt');
            $table->boolean('show_category_menu')->default(true)->after('announcement_text');
            $table->string('category_menu_label')->default('All categories')->after('show_category_menu');
            $table->string('default_meta_title')->default('IGI Canada Wholesale Leather Goods')->after('featured_category_ids');
            $table->text('default_meta_description')->nullable()->after('default_meta_title');
            $table->string('og_title')->nullable()->after('default_meta_description');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_image_path')->nullable()->after('og_description');
            $table->string('twitter_card')->default('summary_large_image')->after('og_image_path');
            $table->string('twitter_title')->nullable()->after('twitter_card');
            $table->text('twitter_description')->nullable()->after('twitter_title');
            $table->string('twitter_image_path')->nullable()->after('twitter_description');
        });

        DB::table('homepage_settings')->update([
            'default_meta_description' => 'Wholesale leather goods and accessories for approved retail partners across Canada and worldwide.',
            'og_title' => 'IGI Canada Wholesale Leather Goods',
            'og_description' => 'Explore the IGI Canada wholesale catalogue for wallets, bags, belts and leather accessories.',
            'twitter_title' => 'IGI Canada Wholesale Leather Goods',
            'twitter_description' => 'Wholesale leather goods for approved retail partners.',
        ]);
    }

    public function down(): void
    {
        Schema::table('homepage_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'favicon_path',
                'show_category_menu',
                'category_menu_label',
                'default_meta_title',
                'default_meta_description',
                'og_title',
                'og_description',
                'og_image_path',
                'twitter_card',
                'twitter_title',
                'twitter_description',
                'twitter_image_path',
            ]);
        });
    }
};
