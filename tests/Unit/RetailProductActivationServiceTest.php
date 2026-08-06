<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\RetailProductActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetailProductActivationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_enables_active_priced_in_stock_variants(): void
    {
        $product = Product::create([
            'name' => 'Retail Candidate',
            'slug' => 'retail-candidate',
            'visibility' => 'wholesale',
            'is_active' => true,
        ]);
        $ready = $product->variants()->create([
            'retail_price' => 49.99,
            'stock_quantity' => 5,
            'is_available_retail' => false,
            'is_active' => true,
        ]);
        $outOfStock = $product->variants()->create([
            'retail_price' => 39.99,
            'stock_quantity' => 0,
            'is_available_retail' => false,
            'is_active' => true,
        ]);
        $unpriced = $product->variants()->create([
            'retail_price' => null,
            'stock_quantity' => 4,
            'is_available_retail' => false,
            'is_active' => true,
        ]);

        $count = app(RetailProductActivationService::class)->enable($product);

        $this->assertSame(1, $count);
        $this->assertTrue($ready->fresh()->is_available_retail);
        $this->assertFalse($outOfStock->fresh()->is_available_retail);
        $this->assertFalse($unpriced->fresh()->is_available_retail);
        $this->assertSame('both', $product->fresh()->visibility);
    }

    public function test_inactive_product_is_not_enabled(): void
    {
        $product = Product::create([
            'name' => 'Inactive Product',
            'slug' => 'inactive-product',
            'visibility' => 'wholesale',
            'is_active' => false,
        ]);
        $variant = $product->variants()->create([
            'retail_price' => 29.99,
            'stock_quantity' => 3,
            'is_available_retail' => false,
            'is_active' => true,
        ]);

        $this->assertSame(0, app(RetailProductActivationService::class)->enable($product));
        $this->assertFalse($variant->fresh()->is_available_retail);
        $this->assertSame('wholesale', $product->fresh()->visibility);
    }

    public function test_retail_availability_can_be_disabled_without_changing_wholesale_visibility(): void
    {
        $product = Product::create([
            'name' => 'Shared Product',
            'slug' => 'shared-product',
            'visibility' => 'both',
            'is_active' => true,
        ]);
        $variant = $product->variants()->create([
            'retail_price' => 29.99,
            'stock_quantity' => 3,
            'is_available_retail' => true,
            'is_active' => true,
        ]);

        $this->assertSame(1, app(RetailProductActivationService::class)->disable($product));
        $this->assertFalse($variant->fresh()->is_available_retail);
        $this->assertSame('both', $product->fresh()->visibility);
    }
}
