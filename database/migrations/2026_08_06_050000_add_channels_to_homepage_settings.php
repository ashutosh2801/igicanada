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
            $table->string('sales_channel', 20)->default('wholesale')->after('id');
            $table->unique('sales_channel');
        });

        $now = now();

        DB::table('homepage_settings')->insert([
            'sales_channel' => 'retail',
            'brand_name' => 'Leather Wallets',
            'logo_alt' => 'Leather Wallets Canada',
            'announcement_text' => 'Leather goods for everyday carry · Shipping across Canada & the USA',
            'show_category_menu' => false,
            'category_menu_label' => 'Shop',
            'hero_eyebrow' => 'Leather Wallets Canada',
            'hero_title' => 'Everyday leather, made better.',
            'hero_description' => 'Timeless wallets and accessories selected for the way you carry every day.',
            'hero_primary_label' => 'Shop the collection',
            'hero_primary_url' => '/shop',
            'catalogue_eyebrow' => 'Shared catalogue · Retail selection',
            'catalogue_title' => 'New arrivals',
            'catalogue_description' => 'Discover the latest leather wallets and accessories available for retail.',
            'default_meta_title' => 'Leather Wallets Canada',
            'default_meta_description' => 'Shop leather wallets and everyday accessories from Leather Wallets Canada.',
            'og_title' => 'Leather Wallets Canada',
            'og_description' => 'Leather wallets and accessories, thoughtfully selected for everyday carry.',
            'twitter_card' => 'summary_large_image',
            'twitter_title' => 'Leather Wallets Canada',
            'twitter_description' => 'Leather wallets and accessories for everyday carry.',
            'show_new_arrivals' => true,
            'new_arrivals_eyebrow' => 'Shared catalogue · Retail selection',
            'new_arrivals_title' => 'New arrivals',
            'new_arrivals_description' => 'Discover the latest leather wallets and accessories available for retail.',
            'new_arrivals_count' => 8,
            'footer_description' => 'Everyday leather, made better.',
            'footer_copyright' => 'Leather Wallets Canada. All rights reserved.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('homepage_settings')->where('sales_channel', 'retail')->delete();

        Schema::table('homepage_settings', function (Blueprint $table): void {
            $table->dropUnique(['sales_channel']);
            $table->dropColumn('sales_channel');
        });
    }
};
