<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, array{name: string, description: string}> */
    private array $products = [
        '4615' => ['name' => 'Genuine Cowhide Biker Wallet', 'description' => 'A genuine cowhide leather biker wallet with practical everyday organization and a classic chain-wallet profile. Choose an available colour above.'],
        '5191' => ['name' => 'RFID Leather Wallet – Brown', 'description' => 'A genuine cowhide leather wallet in rich brown, designed with RFID-blocking protection for confident everyday carry.'],
        '8501R' => ['name' => 'Mini Leather Card Wallet – Black', 'description' => 'A compact genuine cowhide leather card wallet with gusseted storage for credit cards or business cards.'],
        '7601R' => ['name' => 'Women’s RFID Trifold Wallet', 'description' => 'A medium genuine cowhide leather trifold wallet with RFID-blocking protection, twelve card slots and an ID window.'],
        '7502R' => ['name' => 'Women’s Cowhide RFID Wallet', 'description' => 'A versatile genuine cowhide leather wallet with RFID-blocking protection and multiple colour options for everyday use.'],
        '7616R' => ['name' => 'Women’s RFID Double-Zip Wallet', 'description' => 'A genuine cowhide leather wallet with RFID-blocking protection and a practical double-zip design for organized carry.'],
        '4059' => ['name' => 'Lambskin RFID Wallet with Coin Pocket', 'description' => 'A soft genuine lambskin leather wallet with RFID-blocking protection and a convenient change pocket.'],
        '4608' => ['name' => 'RFID Coat Wallet with Phone Pocket', 'description' => 'A long genuine cowhide leather coat wallet with RFID-blocking protection, card storage and a dedicated phone pocket.'],
        '4544R' => ['name' => 'Men’s Cowhide RFID Wallet', 'description' => 'A classic men’s wallet crafted from genuine cowhide leather with RFID-blocking protection and useful everyday storage.'],
        '7507R' => ['name' => 'Women’s 14-Card RFID Wallet', 'description' => 'A medium genuine cowhide leather wallet with RFID-blocking protection, fourteen card slots and an ID window.'],
        '7462P' => ['name' => 'Women’s Medium Cowhide Wallet', 'description' => 'A medium genuine cowhide leather wallet designed for practical organization in a compact everyday format.'],
        '7083' => ['name' => 'Women’s Lambskin Leather Wallet', 'description' => 'A supple genuine lambskin leather wallet with a lightweight feel and classic everyday organization.'],
        '4594R' => ['name' => 'Slim 5-Card RFID Wallet', 'description' => 'A slim genuine leather wallet with five card slots, an ID window and RFID-blocking protection.'],
        '4689LR' => ['name' => 'Men’s 12-Card RFID Wallet', 'description' => 'A genuine cowhide leather wallet with RFID-blocking protection, twelve card slots and a practical change pocket.'],
        '4576R' => ['name' => 'RFID Executive Coat Wallet', 'description' => 'A streamlined genuine cowhide leather coat wallet with RFID-blocking protection and an executive long-wallet profile.'],
        '7549R' => ['name' => 'Women’s Medium RFID Wallet', 'description' => 'A medium genuine cowhide leather wallet with RFID-blocking protection and organized storage for daily essentials.'],
        '7563R' => ['name' => 'Women’s RFID Zip-Around Wallet', 'description' => 'A genuine cowhide leather zip-around wallet with RFID-blocking protection and a secure full-zip closure.'],
        '4514R' => ['name' => 'Men’s Slim Cowhide RFID Wallet', 'description' => 'A slim genuine cowhide leather wallet with RFID-blocking protection, offered in versatile everyday colours.'],
        '7503R' => ['name' => 'Women’s RFID Organizer Wallet', 'description' => 'A genuine cowhide leather RFID wallet with a separate card section for clear, convenient organization.'],
        '7509R' => ['name' => 'Women’s Cowhide RFID Zip Wallet', 'description' => 'A genuine cowhide leather zipper wallet with RFID-blocking protection and secure everyday storage.'],
    ];

    public function up(): void
    {
        DB::table('homepage_settings')->where('sales_channel', 'retail')->update([
            'logo_path' => '/assets/retail/leather-wallets-logo.svg',
            'logo_alt' => 'Leather Wallets Canada',
            'favicon_path' => '/assets/retail/leather-wallets-favicon.svg',
            'hero_image_paths' => json_encode([
                '/assets/retail/heroes/leatherwallets-hero-red-studio.webp',
                '/assets/retail/heroes/leatherwallets-hero-white-studio.webp',
            ]),
            'hero_slider_interval' => 6,
            'hero_eyebrow' => 'Leather Wallets · Canada',
            'hero_title' => 'Everyday leather. Distinctly yours.',
            'hero_description' => 'Wallets and carry essentials selected for modern life, with timeless materials and thoughtful details.',
            'hero_primary_label' => 'Shop wallets',
            'hero_primary_url' => '/shop',
            'hero_secondary_label' => 'New arrivals',
            'hero_secondary_url' => '/#new-arrivals',
            'catalogue_eyebrow' => null,
            'catalogue_title' => 'Shop by category',
            'catalogue_description' => 'Explore wallets and everyday leather essentials, curated from our shared Canadian catalogue.',
            'new_arrivals_eyebrow' => 'Just in',
            'new_arrivals_title' => 'New arrivals',
            'new_arrivals_description' => 'Fresh wallet styles and everyday leather essentials, ready to ship.',
            'new_arrivals_count' => 8,
            'updated_at' => now(),
        ]);

        foreach ($this->products as $sku => $content) {
            $product = DB::table('products')->where('sku', $sku)->first(['id', 'visibility']);

            if (! $product) {
                continue;
            }

            DB::table('products')->where('id', $product->id)->update([
                'retail_name' => $content['name'],
                'retail_description' => '<p>'.$content['description'].'</p><p>Available options, pricing and current stock are shown above.</p>',
                'visibility' => $product->visibility === 'retail' ? 'retail' : 'both',
                'updated_at' => now(),
            ]);

            DB::table('product_variants')
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->where('retail_price', '>', 0)
                ->where('stock_quantity', '>', 0)
                ->update(['is_available_retail' => true, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach ($this->products as $sku => $content) {
            $product = DB::table('products')->where('sku', $sku)->where('retail_name', $content['name'])->first(['id']);

            if (! $product) {
                continue;
            }

            DB::table('product_variants')->where('product_id', $product->id)->update(['is_available_retail' => false]);
            DB::table('products')->where('id', $product->id)->update([
                'retail_name' => null,
                'retail_description' => null,
                'visibility' => 'wholesale',
            ]);
        }
    }
};
