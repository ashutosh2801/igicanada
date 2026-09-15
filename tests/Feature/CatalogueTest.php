<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalogue_lists_products_without_exposing_wholesale_prices(): void
    {
        $category = Category::create(['name' => 'Wallets', 'slug' => 'wallets', 'is_active' => true]);
        $product = Product::create([
            'sku' => 'W100', 'name' => 'Cowhide Wallet', 'slug' => 'cowhide-wallet',
            'visibility' => 'wholesale', 'is_active' => true,
        ]);
        $product->categories()->attach($category);
        $product->variants()->create([
            'wholesale_price' => 10, 'retail_price' => 20, 'stock_quantity' => 5,
            'wholesale_minimum_quantity' => 2, 'is_active' => true,
        ]);

        $response = $this->get('/catalogue');

        $response->assertSuccessful()->assertInertia(fn (Assert $page) => $page
            ->component('Catalogue/Index')
            ->where('products.data.0.name', 'Cowhide Wallet')
            ->where('products.data.0.inStock', true)
            ->where('products.data.0.accountPrice', null)
            ->where('pricing.authorized', false)
            ->missing('products.data.0.wholesale_price')
            ->has('categories', 1));
    }

    public function test_public_product_detail_does_not_expose_wholesale_price(): void
    {
        $product = Product::create([
            'sku' => 'W100', 'name' => 'Cowhide Wallet', 'slug' => 'cowhide-wallet',
            'visibility' => 'wholesale', 'is_active' => true,
        ]);
        $product->variants()->create([
            'wholesale_price' => 10, 'retail_price' => 20, 'stock_quantity' => 5,
            'wholesale_minimum_quantity' => 2, 'is_active' => true,
        ]);

        $this->get('/catalogue/cowhide-wallet')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalogue/Show')
                ->where('pricing.authorized', false)
                ->where('product.variants.0.wholesalePrice', null)
                ->where('product.variants.0.accountPrice', null)
                ->where('product.variants.0.stockQuantity', null));
    }

    public function test_product_detail_exposes_rich_description_color_swatch_and_multiple_sizes(): void
    {
        $product = Product::create([
            'sku' => 'B100',
            'name' => 'Vintage Bag',
            'slug' => 'vintage-bag',
            'description' => '<p>A <strong>handmade</strong> leather bag.</p>',
            'visibility' => 'wholesale',
            'is_active' => true,
        ]);
        $product->variants()->create([
            'color' => 'Vintage Brown',
            'color_code' => '#8b4513',
            'sizes' => ['Small', 'Medium', 'Large'],
            'wholesale_price' => 25,
            'stock_quantity' => 5,
            'wholesale_minimum_quantity' => 1,
            'is_active' => true,
        ]);

        $this->get('/catalogue/vintage-bag')->assertInertia(fn (Assert $page) => $page
            ->where('product.description', '<p>A <strong>handmade</strong> leather bag.</p>')
            ->where('product.variants.0.color', 'Vintage Brown')
            ->where('product.variants.0.colorCode', '#8b4513')
            ->where('product.variants.0.sizes', ['Small', 'Medium', 'Large']));
    }

    public function test_reusable_media_assets_supply_primary_and_gallery_images(): void
    {
        $primaryImage = MediaAsset::create([
            'disk' => 'public',
            'path' => 'media-library/shared-bag.jpg',
            'filename' => 'shared-bag.jpg',
            'title' => 'Shared bag',
            'alt_text' => 'Brown leather bag',
        ]);
        $galleryImage = MediaAsset::create([
            'disk' => 'public',
            'path' => 'media-library/shared-detail.jpg',
            'filename' => 'shared-detail.jpg',
            'title' => 'Shared detail',
        ]);
        $product = Product::create([
            'name' => 'Reusable Media Bag',
            'slug' => 'reusable-media-bag',
            'visibility' => 'wholesale',
            'is_active' => true,
            'primary_media_asset_id' => $primaryImage->id,
        ]);
        $product->mediaAssets()->attach([$primaryImage->id, $galleryImage->id]);
        $otherProduct = Product::create([
            'name' => 'Other Bag',
            'slug' => 'other-reusable-bag',
            'visibility' => 'wholesale',
            'is_active' => true,
            'primary_media_asset_id' => $primaryImage->id,
        ]);

        $this->get('/catalogue/reusable-media-bag')->assertInertia(fn (Assert $page) => $page
            ->has('product.images', 2)
            ->where('product.images.0.src', $primaryImage->url())
            ->where('product.images.0.alt', 'Brown leather bag')
            ->where('product.images.1.src', $galleryImage->url()));

        $this->assertSame($primaryImage->id, $otherProduct->primary_media_asset_id);
        $this->assertDatabaseCount('media_assets', 2);
    }

    public function test_approved_reseller_receives_tier_adjusted_pricing(): void
    {
        $tier = PriceTier::create(['name' => 'Level 2', 'discount_percentage' => 20, 'is_active' => true]);
        $user = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'approved',
            'price_tier_id' => $tier->id,
        ]);
        $product = Product::create([
            'sku' => 'W100', 'name' => 'Cowhide Wallet', 'slug' => 'cowhide-wallet',
            'visibility' => 'wholesale', 'is_active' => true,
        ]);
        $product->variants()->create([
            'wholesale_price' => 10, 'retail_price' => 20, 'stock_quantity' => 5,
            'wholesale_minimum_quantity' => 2, 'is_active' => true,
        ]);

        $this->actingAs($user)->get('/catalogue/cowhide-wallet')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('pricing.authorized', true)
                ->where('pricing.tier', 'Level 2')
                ->where('product.variants.0.wholesalePrice', '10.00')
                ->where('product.variants.0.accountPrice', '8.00')
                ->where('product.variants.0.stockQuantity', 5));

        $this->actingAs($user)->get('/catalogue')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('pricing.authorized', true)
                ->where('pricing.tier', 'Level 2')
                ->where('products.data.0.wholesalePrice', '10.00')
                ->where('products.data.0.accountPrice', '8.00'));
    }

    public function test_product_detail_shows_four_similar_products_without_public_prices(): void
    {
        $category = Category::create(['name' => 'Bags', 'slug' => 'bags', 'is_active' => true]);
        $product = Product::create([
            'sku' => 'B100', 'name' => 'Main Bag', 'slug' => 'main-bag',
            'visibility' => 'wholesale', 'is_active' => true,
        ]);
        $product->categories()->attach($category);
        $product->variants()->create([
            'wholesale_price' => 50, 'stock_quantity' => 5,
            'wholesale_minimum_quantity' => 1, 'is_active' => true,
        ]);

        foreach (range(1, 5) as $index) {
            $similar = Product::create([
                'sku' => "B10{$index}", 'name' => "Similar Bag {$index}", 'slug' => "similar-bag-{$index}",
                'visibility' => 'wholesale', 'is_active' => true,
            ]);
            if ($index <= 3) {
                $similar->categories()->attach($category);
            }
            $similar->variants()->create([
                'wholesale_price' => 20 + $index, 'stock_quantity' => 5,
                'wholesale_minimum_quantity' => 1, 'is_active' => true,
            ]);
        }

        $this->get('/catalogue/main-bag')->assertInertia(fn (Assert $page) => $page
            ->has('similarProducts', 4)
            ->where('similarProducts.0.name', 'Similar Bag 1')
            ->where('similarProducts.1.name', 'Similar Bag 2')
            ->where('similarProducts.2.name', 'Similar Bag 3')
            ->where('similarProducts.0.accountPrice', null)
            ->where('similarProducts.0.wholesalePrice', null));
    }

    public function test_retail_only_legacy_products_are_not_available_on_wholesale_website(): void
    {
        $product = Product::create([
            'sku' => 'R100', 'name' => 'Retail Archive', 'slug' => 'retail-archive',
            'visibility' => 'retail', 'is_active' => true,
        ]);
        $product->variants()->create([
            'retail_price' => 20, 'stock_quantity' => 5,
            'wholesale_minimum_quantity' => 1, 'is_active' => true,
        ]);

        $this->get('/catalogue')->assertInertia(fn (Assert $page) => $page
            ->where('products.total', 0));
        $this->get('/catalogue/retail-archive')->assertNotFound();
    }

    public function test_approved_reseller_sees_original_price_when_compare_at_price_is_set(): void
    {
        $tier = PriceTier::create(['name' => 'Level 1', 'discount_percentage' => 10, 'is_active' => true]);
        $user = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'approved',
            'price_tier_id' => $tier->id,
        ]);
        $product = Product::create([
            'sku' => 'W100', 'name' => 'Cowhide Wallet', 'slug' => 'cowhide-wallet',
            'visibility' => 'wholesale', 'is_active' => true,
        ]);
        $product->variants()->create([
            'wholesale_price' => 10,
            'wholesale_compare_at_price' => 15,
            'stock_quantity' => 5,
            'wholesale_minimum_quantity' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($user)->get('/catalogue/cowhide-wallet')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('pricing.authorized', true)
                ->where('product.variants.0.wholesalePrice', '10.00')
                ->where('product.variants.0.accountPrice', '9.00')
                ->where('product.variants.0.compareAtPrice', '15.00'));

        $this->actingAs($user)->get('/catalogue')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('products.data.0.wholesalePrice', '10.00')
                ->where('products.data.0.accountPrice', '9.00')
                ->where('products.data.0.compareAtPrice', '15.00'));
    }

    public function test_clearance_lists_only_products_with_both_wholesale_prices(): void
    {
        $clearanceProduct = Product::create([
            'sku' => 'W101', 'name' => 'Clearance Wallet', 'slug' => 'clearance-wallet',
            'visibility' => 'wholesale', 'is_active' => true,
        ]);
        $clearanceProduct->variants()->create([
            'wholesale_price' => 10,
            'wholesale_compare_at_price' => 15,
            'stock_quantity' => 5,
            'wholesale_minimum_quantity' => 2,
            'is_active' => true,
        ]);

        $fullPriceProduct = Product::create([
            'sku' => 'W102', 'name' => 'Regular Wallet', 'slug' => 'regular-wallet',
            'visibility' => 'wholesale', 'is_active' => true,
        ]);
        $fullPriceProduct->variants()->create([
            'wholesale_price' => 20,
            'stock_quantity' => 5,
            'wholesale_minimum_quantity' => 2,
            'is_active' => true,
        ]);

        Product::create([
            'sku' => 'W103', 'name' => 'Legacy Retail Only', 'slug' => 'legacy-retail-only',
            'visibility' => 'retail', 'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'approved',
        ]))->get('/clearance')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalogue/Index')
                ->where('clearance', true)
                ->where('products.total', 1)
                ->where('products.data.0.name', 'Clearance Wallet')
                ->where('products.data.0.compareAtPrice', '15.00'));

        $this->get('/catalogue')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('clearance')
                ->where('products.total', 2));
    }

    public function test_wholesale_header_navigation_includes_a_clearance_menu_item(): void
    {
        $this->get('/clearance')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('storefront.headerNavigation.0.label', 'Clearance')
                ->where('storefront.headerNavigation.0.url', '/clearance'));
    }

    public function test_guest_does_not_see_compare_at_price_on_wholesale(): void
    {
        $product = Product::create([
            'sku' => 'W100', 'name' => 'Cowhide Wallet', 'slug' => 'cowhide-wallet',
            'visibility' => 'wholesale', 'is_active' => true,
        ]);
        $product->variants()->create([
            'wholesale_price' => 10,
            'wholesale_compare_at_price' => 15,
            'stock_quantity' => 5,
            'wholesale_minimum_quantity' => 2,
            'is_active' => true,
        ]);

        $this->get('/catalogue/cowhide-wallet')->assertInertia(fn (Assert $page) => $page
            ->where('product.variants.0.compareAtPrice', null));

        $this->get('/catalogue')->assertInertia(fn (Assert $page) => $page
            ->where('products.data.0.compareAtPrice', null));
    }
}
