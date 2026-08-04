<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('account_type', '!=', 'admin')->update(['account_type' => 'wholesale']);

        DB::table('products')->where('visibility', 'retail')->update([
            'visibility' => 'wholesale',
            'is_active' => false,
        ]);
        DB::table('products')->where('visibility', 'both')->update(['visibility' => 'wholesale']);

        DB::table('navigation_items')->where('url', '/wholesale/apply')->update(['label' => 'Apply for a wholesale account']);

        DB::table('homepage_settings')->update([
            'announcement_text' => 'Wholesale leather goods · For approved wholesale accounts',
            'hero_eyebrow' => 'Wholesale business starts here',
            'hero_description' => 'Explore proven wallets, bags, belts and accessories with protected wholesale account pricing, practical minimums and support from our Mississauga team.',
            'footer_description' => 'Wholesale leather goods and accessories for approved wholesale businesses in Canada and worldwide.',
            'default_meta_description' => 'Wholesale leather goods and accessories for approved wholesale businesses across Canada and worldwide.',
            'twitter_description' => 'Wholesale leather goods for approved wholesale accounts.',
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('account_type', 'wholesale')->update(['account_type' => 'reseller']);
    }
};
