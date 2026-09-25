<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('homepage_settings')->where('sales_channel', 'walletsandbelts')->exists()) {
            return;
        }

        $retail = DB::table('homepage_settings')->where('sales_channel', 'retail')->first();

        if ($retail === null) {
            return;
        }

        $row = (array) $retail;
        unset($row['id']);

        $row['sales_channel'] = 'walletsandbelts';
        $row['brand_name'] = 'Wallets and Belts';
        $row['logo_alt'] = 'Wallets and Belts';
        $row['announcement_text'] = 'Wallets, belts and leather goods for everyday carry · Shipping across Canada & the USA';
        $row['hero_eyebrow'] = 'Wallets and Belts';
        $row['hero_title'] = 'Everyday leather, made better.';
        $row['catalogue_eyebrow'] = 'Leather goods · Wallets and Belts';
        $row['new_arrivals_eyebrow'] = 'Leather goods · Retail selection';
        $row['default_meta_title'] = 'Wallets and Belts';
        $row['default_meta_description'] = 'Shop wallets, belts and leather accessories from Wallets and Belts.';
        $row['og_title'] = 'Wallets and Belts';
        $row['og_description'] = 'Wallets, belts and leather goods for everyday carry.';
        $row['twitter_title'] = 'Wallets and Belts';
        $row['twitter_description'] = 'Wallets, belts and leather accessories for everyday carry.';
        $row['footer_description'] = 'Everyday leather, made better.';
        $row['footer_copyright'] = 'Wallets and Belts. All rights reserved.';

        $row['created_at'] = now();
        $row['updated_at'] = now();

        DB::table('homepage_settings')->insert($row);
    }

    public function down(): void
    {
        DB::table('homepage_settings')->where('sales_channel', 'walletsandbelts')->delete();
    }
};