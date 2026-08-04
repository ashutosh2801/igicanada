<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('brand_name')->default('IGI Canada');
            $table->string('logo_path')->nullable();
            $table->string('logo_alt')->default('IGI Canada');
            $table->string('announcement_text')->nullable();
            $table->string('hero_eyebrow')->nullable();
            $table->string('hero_title');
            $table->text('hero_description')->nullable();
            $table->string('hero_image_path')->nullable();
            $table->string('hero_primary_label')->nullable();
            $table->string('hero_primary_url')->nullable();
            $table->string('hero_secondary_label')->nullable();
            $table->string('hero_secondary_url')->nullable();
            $table->string('catalogue_eyebrow')->nullable();
            $table->string('catalogue_title');
            $table->text('catalogue_description')->nullable();
            $table->json('featured_category_ids')->nullable();
            $table->text('footer_description')->nullable();
            $table->string('footer_address')->nullable();
            $table->string('footer_phone')->nullable();
            $table->string('footer_email')->nullable();
            $table->string('footer_copyright')->nullable();
            $table->timestamps();
        });

        Schema::create('navigation_items', function (Blueprint $table): void {
            $table->id();
            $table->string('location')->index();
            $table->string('label');
            $table->string('url');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('opens_new_tab')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('homepage_settings')->insert([
            'brand_name' => 'IGI Canada',
            'logo_alt' => 'IGI Canada',
            'announcement_text' => 'Wholesale leather goods · For approved retail partners',
            'hero_eyebrow' => 'Independent retail starts here',
            'hero_title' => 'Wholesale leather goods, selected to sell through.',
            'hero_description' => 'Explore proven wallets, bags, belts and accessories with protected reseller pricing, practical minimums and support from our Mississauga team.',
            'hero_primary_label' => 'Explore wholesale catalogue',
            'hero_primary_url' => '/catalogue',
            'hero_secondary_label' => 'Apply for an account',
            'hero_secondary_url' => '/wholesale/apply',
            'catalogue_eyebrow' => 'Shop by category',
            'catalogue_title' => 'Build your next best-selling assortment.',
            'catalogue_description' => 'Browse active wholesale categories and sign in to unlock account pricing and ordering.',
            'footer_description' => 'Wholesale leather goods and accessories for independent retailers in Canada and worldwide.',
            'footer_address' => '966 Pantera Dr., Unit 7, Mississauga, ON L4W 2S1, Canada',
            'footer_phone' => '905-625-8831',
            'footer_email' => 'newigi@gmail.com',
            'footer_copyright' => 'IGI Canada. All rights reserved.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('navigation_items')->insert([
            ['location' => 'header', 'label' => 'Catalogue', 'url' => '/catalogue', 'sort_order' => 10, 'opens_new_tab' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'header', 'label' => 'Wholesale account', 'url' => '/wholesale/apply', 'sort_order' => 20, 'opens_new_tab' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'header', 'label' => 'Contact', 'url' => '/contact', 'sort_order' => 30, 'opens_new_tab' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'footer', 'label' => 'Wholesale catalogue', 'url' => '/catalogue', 'sort_order' => 10, 'opens_new_tab' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'footer', 'label' => 'Become a reseller', 'url' => '/wholesale/apply', 'sort_order' => 20, 'opens_new_tab' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['location' => 'footer', 'label' => 'Contact us', 'url' => '/contact', 'sort_order' => 30, 'opens_new_tab' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('homepage_settings');
    }
};
