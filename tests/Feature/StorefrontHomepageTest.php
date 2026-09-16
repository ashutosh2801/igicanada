<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HomepageSetting;
use App\Models\NavigationItem;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StorefrontHomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_content_and_navigation_are_database_managed(): void
    {
        HomepageSetting::firstOrFail()->update([
            'hero_title' => 'A custom wholesale headline',
            'hero_image_paths' => ['storefront/hero/first.webp', 'storefront/hero/second.webp'],
            'hero_slider_interval' => 7,
            'catalogue_title' => 'Featured wholesale ranges',
        ]);
        NavigationItem::create([
            'location' => 'header',
            'label' => 'New arrivals',
            'url' => '/catalogue?q=new',
            'sort_order' => 5,
            'is_active' => true,
        ]);
        $category = Category::create(['name' => 'Wallets', 'slug' => 'wallets', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Wholesale Wallet',
            'slug' => 'wholesale-wallet',
            'visibility' => 'wholesale',
            'is_active' => true,
        ]);
        $product->categories()->attach($category);

        $this->get('/')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('homepage.heroTitle', 'A custom wholesale headline')
            ->where('homepage.heroImages', ['/storage/storefront/hero/first.webp', '/storage/storefront/hero/second.webp'])
            ->where('homepage.heroSliderInterval', 7)
            ->where('homepage.catalogueTitle', 'Featured wholesale ranges')
            ->where('categories.0.name', 'Wallets')
            ->where('storefront.headerNavigation.0.label', 'Products')
            ->where('storefront.headerNavigation.0.url', '/catalogue')
            ->where('storefront.headerNavigation.1.label', 'Clearance')
            ->where('storefront.headerNavigation.2.label', 'New arrivals'));
    }

    public function test_header_cart_count_is_shared_for_approved_reseller(): void
    {
        $user = User::factory()->create([
            'name' => 'Ashutosh Gupta',
            'account_type' => 'wholesale',
            'approval_status' => 'approved',
        ]);
        $product = Product::create([
            'name' => 'Wholesale Wallet',
            'slug' => 'wholesale-wallet',
            'visibility' => 'wholesale',
            'is_active' => true,
        ]);
        $variant = $product->variants()->create([
            'wholesale_price' => 10,
            'wholesale_minimum_quantity' => 2,
            'stock_quantity' => 20,
            'is_active' => true,
        ]);
        $cart = $user->cart()->create();
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 4]);

        $this->actingAs($user)->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('storefront.accountNavigation.label', 'Ashutosh')
            ->where('storefront.accountNavigation.url', '/account')
            ->where('storefront.accountNavigation.items.0.label', 'Orders')
            ->where('storefront.accountNavigation.items.0.url', '/orders')
            ->where('storefront.accountNavigation.items.1.label', 'Addresses')
            ->where('storefront.accountNavigation.items.1.url', '/account/addresses')
            ->where('storefront.accountNavigation.items.2.label', 'Profile')
            ->where('storefront.accountNavigation.items.2.url', '/account')
            ->where('storefront.accountNavigation.items.3.label', 'Logout')
            ->where('storefront.accountNavigation.items.3.url', '/logout')
            ->where('storefront.accountNavigation.items.3.method', 'post')
            ->where('storefront.cartNavigation.url', '/cart')
            ->where('storefront.cartNavigation.available', true)
            ->where('storefront.cartCount', 4)
            ->where('storefront.cartSummary.items.0.product', 'Wholesale Wallet')
            ->where('storefront.cartSummary.items.0.quantity', 4)
            ->where('storefront.cartSummary.items.0.unitPrice', '10.00')
            ->where('storefront.cartSummary.items.0.lineTotal', '40.00')
            ->where('storefront.cartSummary.subtotal', '40.00'));
    }

    public function test_homepage_shows_latest_active_wholesale_products_as_new_arrivals(): void
    {
        HomepageSetting::firstOrFail()->update([
            'show_new_arrivals' => true,
            'new_arrivals_title' => 'Just landed',
            'new_arrivals_count' => 4,
        ]);

        $older = Product::create([
            'name' => 'Older Wholesale Product',
            'slug' => 'older-wholesale-product',
            'visibility' => 'wholesale',
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);
        $newer = Product::create([
            'name' => 'Newest Wholesale Product',
            'slug' => 'newest-wholesale-product',
            'visibility' => 'wholesale',
            'is_active' => true,
            'published_at' => now(),
        ]);
        Product::create([
            'name' => 'Retail Only Product',
            'slug' => 'retail-only-product',
            'visibility' => 'retail',
            'is_active' => true,
            'published_at' => now()->addDay(),
        ]);
        Product::create([
            'name' => 'Inactive Wholesale Product',
            'slug' => 'inactive-wholesale-product',
            'visibility' => 'wholesale',
            'is_active' => false,
            'published_at' => now()->addDays(2),
        ]);

        $older->variants()->create(['is_active' => true, 'stock_quantity' => 0]);
        $newer->variants()->create(['is_active' => true, 'stock_quantity' => 12]);

        $this->get('/')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
            ->where('homepage.showNewArrivals', true)
            ->where('homepage.newArrivalsTitle', 'Just landed')
            ->has('newArrivals', 2)
            ->where('newArrivals.0.name', 'Newest Wholesale Product')
            ->where('newArrivals.0.inStock', true)
            ->where('newArrivals.1.name', 'Older Wholesale Product')
            ->where('newArrivals.1.inStock', false));
    }

    public function test_approved_wholesale_account_sees_tier_price_on_homepage_new_arrivals(): void
    {
        $tier = PriceTier::create(['name' => 'Level 2', 'discount_percentage' => 20, 'is_active' => true]);
        $user = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'approved',
            'price_tier_id' => $tier->id,
        ]);
        $product = Product::create([
            'name' => 'Tier Priced Wallet',
            'slug' => 'tier-priced-wallet',
            'visibility' => 'wholesale',
            'is_active' => true,
            'published_at' => now(),
        ]);
        $product->variants()->create([
            'wholesale_price' => 10,
            'stock_quantity' => 12,
            'is_active' => true,
        ]);

        $this->actingAs($user)->get('/')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
            ->where('pricing.authorized', true)
            ->where('pricing.tier', 'Level 2')
            ->where('newArrivals.0.wholesalePrice', '10.00')
            ->where('newArrivals.0.accountPrice', '8.00'));
    }

    public function test_header_uses_role_aware_account_and_cart_links(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('storefront.accountNavigation.url', '/login')
            ->where('storefront.cartNavigation.url', '/login'));

        $admin = User::factory()->create([
            'name' => 'Admin Person',
            'account_type' => 'admin',
            'approval_status' => 'approved',
        ]);

        $this->actingAs($admin)->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('storefront.accountNavigation.label', 'Wholesale login')
            ->where('storefront.accountNavigation.url', '/login')
            ->where('auth.user', null)
            ->where('storefront.cartNavigation.url', '/login')
            ->where('storefront.cartNavigation.available', false));

        $pending = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'pending',
        ]);

        $this->actingAs($pending)->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('storefront.accountNavigation.label', 'Account status')
            ->where('storefront.accountNavigation.url', '/account/status')
            ->where('storefront.cartNavigation.url', null));

        $customer = User::factory()->create([
            'name' => 'Maria Silva',
            'account_type' => 'wholesale',
            'approval_status' => 'approved',
        ]);

        $this->actingAs($customer)->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('storefront.accountNavigation.label', 'Maria')
            ->where('storefront.accountNavigation.url', '/account')
            ->where('auth.user.name', 'Maria Silva')
            ->where('auth.user.account_type', 'wholesale'));
    }

    public function test_global_search_finds_wholesale_content_and_excludes_retail_archive(): void
    {
        Product::create([
            'sku' => 'WHOLE-100',
            'name' => 'Searchable Wholesale Wallet',
            'slug' => 'searchable-wholesale-wallet',
            'visibility' => 'wholesale',
            'is_active' => true,
        ]);
        Product::create([
            'sku' => 'RETAIL-100',
            'name' => 'Searchable Retail Wallet',
            'slug' => 'searchable-retail-wallet',
            'visibility' => 'retail',
            'is_active' => true,
        ]);

        $this->get('/search?q=Searchable')->assertSuccessful()->assertInertia(fn (Assert $page) => $page
            ->component('Search/Index')
            ->has('results', 1)
            ->where('results.0.title', 'Searchable Wholesale Wallet')
            ->missing('results.0.wholesale_price'));
    }

    public function test_shared_header_exposes_three_level_admin_managed_category_menu(): void
    {
        HomepageSetting::firstOrFail()->update([
            'show_category_menu' => true,
            'category_menu_label' => 'Shop all categories',
        ]);
        $root = Category::create(['name' => 'Men', 'slug' => 'men', 'position' => 1, 'is_active' => true]);
        $child = Category::create(['parent_id' => $root->id, 'name' => 'Wallets', 'slug' => 'mens-wallets', 'position' => 1, 'is_active' => true]);
        $grandchild = Category::create(['parent_id' => $child->id, 'name' => 'Chain wallets', 'slug' => 'chain-wallets', 'position' => 1, 'is_active' => true]);
        Category::create(['parent_id' => $grandchild->id, 'name' => 'Hidden fourth level', 'slug' => 'fourth-level', 'position' => 1, 'is_active' => true]);

        $this->get('/catalogue')->assertInertia(fn (Assert $page) => $page
            ->where('storefront.showCategoryMenu', true)
            ->where('storefront.categoryMenuLabel', 'Shop all categories')
            ->where('storefront.categoryNavigation.0.name', 'Men')
            ->where('storefront.categoryNavigation.0.children.0.name', 'Wallets')
            ->where('storefront.categoryNavigation.0.children.0.children.0.name', 'Chain wallets')
            ->has('storefront.categoryNavigation.0.children.0.children.0.children', 0));
    }

    public function test_admin_managed_default_seo_and_social_metadata_render_in_document_head(): void
    {
        HomepageSetting::firstOrFail()->update([
            'favicon_path' => 'storefront/branding/favicon.png',
            'default_meta_title' => 'Custom wholesale title',
            'default_meta_description' => 'Custom wholesale description.',
            'og_title' => 'Custom social title',
            'og_description' => 'Custom social description.',
            'twitter_card' => 'summary',
        ]);

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('<title inertia>Custom wholesale title</title>', false)
            ->assertSee('<meta name="description" content="Custom wholesale description.">', false)
            ->assertSee('<meta property="og:title" content="Custom social title">', false)
            ->assertSee('<meta name="twitter:card" content="summary">', false)
            ->assertSee('<link rel="icon" href="/storage/storefront/branding/favicon.png">', false);
    }
}
