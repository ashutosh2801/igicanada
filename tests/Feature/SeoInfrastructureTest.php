<?php

namespace Tests\Feature;

use App\Models\ContentPage;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_storefront_has_a_domain_specific_sitemap(): void
    {
        Product::create([
            'name' => 'Shared SEO Wallet',
            'retail_name' => 'Retail SEO Wallet',
            'slug' => 'shared-seo-wallet',
            'visibility' => 'both',
            'is_active' => true,
        ]);
        ContentPage::create([
            'sales_channel' => 'retail',
            'title' => 'Retail Policy',
            'slug' => 'retail-policy',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get('https://leatherwallets.ca/sitemap.xml')
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('https://leatherwallets.ca/products/shared-seo-wallet', false)
            ->assertSee('https://leatherwallets.ca/policies/retail-policy', false)
            ->assertDontSee('https://igicanada.ca', false);

        $this->get('https://igicanada.ca/sitemap.xml')
            ->assertSuccessful()
            ->assertSee('https://igicanada.ca/catalogue/shared-seo-wallet', false)
            ->assertDontSee('https://leatherwallets.ca', false);
    }

    public function test_each_storefront_has_robots_rules_and_its_own_sitemap_reference(): void
    {
        $this->get('https://leatherwallets.ca/robots.txt')
            ->assertSuccessful()
            ->assertSee("Disallow: /checkout\n", false)
            ->assertSee('Sitemap: https://leatherwallets.ca/sitemap.xml', false);

        $this->get('https://igicanada.ca/robots.txt')
            ->assertSuccessful()
            ->assertSee("Disallow: /admin\n", false)
            ->assertSee('Sitemap: https://igicanada.ca/sitemap.xml', false);
    }

    public function test_shared_products_are_available_on_both_public_product_routes(): void
    {
        $product = Product::create([
            'name' => 'Shared Route Wallet',
            'retail_name' => 'Retail Shared Route Wallet',
            'slug' => 'shared-route-wallet',
            'visibility' => 'both',
            'is_active' => true,
        ]);
        $product->variants()->create([
            'wholesale_price' => 20,
            'retail_price' => 49,
            'stock_quantity' => 5,
            'is_available_wholesale' => true,
            'is_available_retail' => true,
            'is_active' => true,
        ]);

        $this->get('https://igicanada.ca/catalogue/shared-route-wallet')->assertSuccessful();
        $this->get('https://leatherwallets.ca/products/shared-route-wallet')->assertSuccessful();
    }
}
