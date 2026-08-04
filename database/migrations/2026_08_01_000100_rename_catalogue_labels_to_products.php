<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('navigation_items')
            ->where('url', '/catalogue')
            ->update(['label' => 'Products']);

        DB::table('homepage_settings')
            ->where('hero_primary_label', 'Explore wholesale catalogue')
            ->update(['hero_primary_label' => 'Explore wholesale products']);

        DB::table('homepage_settings')
            ->where('og_description', 'Explore the IGI Canada wholesale catalogue for wallets, bags, belts and leather accessories.')
            ->update(['og_description' => 'Explore IGI Canada wholesale products including wallets, bags, belts and leather accessories.']);
    }

    public function down(): void
    {
        DB::table('navigation_items')
            ->where('url', '/catalogue')
            ->where('label', 'Products')
            ->update(['label' => DB::raw("CASE WHEN location = 'footer' THEN 'Wholesale catalogue' ELSE 'Catalogue' END")]);

        DB::table('homepage_settings')
            ->where('hero_primary_label', 'Explore wholesale products')
            ->update(['hero_primary_label' => 'Explore wholesale catalogue']);

        DB::table('homepage_settings')
            ->where('og_description', 'Explore IGI Canada wholesale products including wallets, bags, belts and leather accessories.')
            ->update(['og_description' => 'Explore the IGI Canada wholesale catalogue for wallets, bags, belts and leather accessories.']);
    }
};
