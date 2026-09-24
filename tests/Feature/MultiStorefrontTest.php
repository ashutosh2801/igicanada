<?php

namespace Tests\Feature;

use App\Models\HomepageSetting;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MultiStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_retail_domain_uses_its_own_frontend_and_channel(): void
    {
        $retailProduct = Product::create([
            'name' => 'Shared Wallet Name',
            'retail_name' => 'Slim Leather Wallet',
            'slug' => 'slim-leather-wallet',
            'visibility' => 'both',
            'is_active' => true,
            'published_at' => now(),
        ]);
        $retailProduct->variants()->create([
            'retail_price' => 49.99,
            'wholesale_price' => 25,
            'stock_quantity' => 12,
            'is_available_retail' => true,
            'is_available_wholesale' => true,
            'is_active' => true,
        ]);

        $wholesaleOnly = Product::create([
            'name' => 'Wholesale Only Wallet',
            'slug' => 'wholesale-only-wallet',
            'visibility' => 'wholesale',
            'is_active' => true,
        ]);
        $wholesaleOnly->variants()->create([
            'retail_price' => 39.99,
            'stock_quantity' => 5,
            'is_available_retail' => true,
            'is_active' => true,
        ]);

        $this->get('https://leatherwallets.ca/')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('salesChannel', 'retail')
                ->has('products', 1)
                ->where('products.0.name', 'Shared Wallet Name')
                ->where('products.0.price', '49.99'));
    }

    public function test_retail_domain_does_not_expose_wholesale_routes(): void
    {
        $this->get('https://leatherwallets.ca/catalogue')->assertNotFound();
    }

    public function test_product_without_retail_price_is_still_visible_on_both_websites(): void
    {
        $product = Product::create([
            'name' => 'Priced Later Wallet',
            'slug' => 'priced-later-wallet',
            'visibility' => 'both',
            'is_active' => true,
            'published_at' => now(),
        ]);
        $product->variants()->create([
            'wholesale_price' => 25,
            'stock_quantity' => 10,
            'is_available_wholesale' => true,
            'is_available_retail' => true,
            'is_active' => true,
        ]);

        $this->get('https://leatherwallets.ca/shop')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('products.data.0.name', 'Priced Later Wallet')
                ->where('products.data.0.price', null));

        $this->get('https://leatherwallets.ca/products/priced-later-wallet')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('product.variants.0.price', null));

        $this->get('https://igicanada.ca/catalogue')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('products.data.0.name', 'Priced Later Wallet')
                ->where('products.data.0.accountPrice', null)
                ->where('products.total', 1));
    }

    public function test_retail_catalogue_shows_sale_price_and_original_price(): void
    {
        $product = Product::create([
            'name' => 'Shared Wallet Name',
            'slug' => 'slim-leather-wallet',
            'visibility' => 'both',
            'is_active' => true,
            'published_at' => now(),
        ]);
        $product->variants()->create([
            'retail_price' => 39.99,
            'retail_compare_at_price' => 59.99,
            'stock_quantity' => 12,
            'is_available_retail' => true,
            'is_active' => true,
        ]);

        $this->get('https://leatherwallets.ca/shop')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalogue/Index')
                ->where('products.data.0.price', '39.99')
                ->where('products.data.0.compareAtPrice', '59.99'));

        $this->get('https://leatherwallets.ca/products/slim-leather-wallet')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('product.variants.0.price', '39.99')
                ->where('product.variants.0.compareAtPrice', '59.99'));
    }

    public function test_retail_homepage_content_and_brand_are_managed_separately(): void
    {
        HomepageSetting::query()->forChannel('retail')->firstOrFail()->update([
            'brand_name' => 'Leather Wallets Test',
            'announcement_text' => 'Retail announcement',
            'hero_eyebrow' => 'Retail eyebrow',
            'hero_title' => 'A retail-only hero',
            'hero_description' => 'Retail homepage description.',
            'new_arrivals_title' => 'Latest wallets',
            'default_meta_title' => 'Retail SEO title',
        ]);

        $this->get('https://leatherwallets.ca/')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('homepage.heroTitle', 'A retail-only hero')
                ->where('homepage.newArrivalsTitle', 'Latest wallets')
                ->where('homepage.metaTitle', 'Retail SEO title')
                ->where('retailStorefront.brandName', 'Leather Wallets Test')
                ->where('retailStorefront.announcement', 'Retail announcement'));

        $this->assertNotSame(
            'Leather Wallets Test',
            HomepageSetting::query()->forChannel('wholesale')->firstOrFail()->brand_name,
        );
    }

    public function test_retail_homepage_has_deployable_brand_and_hero_assets(): void
    {
        $settings = HomepageSetting::query()->forChannel('retail')->firstOrFail();

        $this->assertSame('/assets/retail/leather-wallets-logo.svg', $settings->logo_path);
        $this->assertSame('/assets/retail/leather-wallets-favicon.svg', $settings->favicon_path);
        $this->assertCount(2, $settings->hero_image_paths);

        foreach ([$settings->logo_path, $settings->favicon_path, ...$settings->hero_image_paths] as $asset) {
            $this->assertFileExists(public_path(ltrim($asset, '/')));
        }

        $this->get('https://leatherwallets.ca/')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('retailStorefront.logoUrl', '/assets/retail/leather-wallets-logo.svg')
                ->has('homepage.heroImages', 2));
    }

    public function test_retail_alias_redirects_to_the_canonical_domain(): void
    {
        $this->get('https://www.leatherwallets.ca/')
            ->assertRedirect('https://leatherwallets.ca/');
    }

    public function test_retail_sibling_domain_serves_retail_storefront_directly(): void
    {
        $product = Product::create([
            'name' => 'Sibling Wallet',
            'retail_name' => 'Slim Leather Wallet',
            'slug' => 'sibling-wallet',
            'visibility' => 'both',
            'is_active' => true,
            'published_at' => now(),
        ]);
        $product->variants()->create([
            'retail_price' => 49.99,
            'stock_quantity' => 12,
            'is_available_retail' => true,
            'is_active' => true,
        ]);

        $this->get('https://walletsandbelts.com/')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('salesChannel', 'retail')
                ->has('products', 1)
                ->where('products.0.name', 'Sibling Wallet'));

        $this->get('https://walletsandbelts.com/shop')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalogue/Index')
                ->where('products.data.0.name', 'Sibling Wallet'));

        $this->get('https://walletsandbelts.com/products/sibling-wallet')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('product.name', 'Sibling Wallet'));
    }

    public function test_retail_sibling_domain_does_not_expose_wholesale_routes(): void
    {
        $this->get('https://walletsandbelts.com/catalogue')->assertNotFound();
    }

    public function test_retail_sibling_domain_is_not_redirected_to_canonical(): void
    {
        $this->get('https://walletsandbelts.com/')->assertSuccessful();
    }

    public function test_wholesale_domain_keeps_the_existing_storefront(): void
    {
        $this->get('https://igicanada.ca/')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('salesChannel', 'wholesale'));
    }
}
