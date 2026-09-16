<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductsTableFiltersTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);
    }

    private function product(string $name, string $slug, array $variant = [], array $attributes = []): Product
    {
        $product = Product::create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'visibility' => 'both',
            'is_active' => true,
        ], $attributes));

        $product->variants()->create(array_merge([
            'sku' => strtoupper($slug),
            'wholesale_price' => 20,
            'retail_price' => 49,
            'stock_quantity' => 10,
            'is_available_wholesale' => true,
            'is_available_retail' => true,
            'is_active' => true,
        ], $variant));

        return $product;
    }

    public function test_category_filter_limits_records(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $leather = Category::create(['name' => 'Leather', 'slug' => 'leather']);
        $canvas = Category::create(['name' => 'Canvas', 'slug' => 'canvas']);

        $in = $this->product('Leather wallet', 'leather-wallet');
        $out = $this->product('Canvas bag', 'canvas-bag');
        $in->categories()->attach($leather);
        $out->categories()->attach($canvas);

        Livewire::test(ListProducts::class)
            ->filterTable('categories', $leather->getKey())
            ->assertCanSeeTableRecords([$in])
            ->assertCanNotSeeTableRecords([$out]);
    }

    public function test_stock_status_filter_limits_records(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $inStock = $this->product('In stock', 'in-stock', ['stock_quantity' => 25]);
        $lowStock = $this->product('Low stock', 'low-stock', ['stock_quantity' => 4]);
        $outOfStock = $this->product('Out of stock', 'out-of-stock', ['stock_quantity' => 0]);
        $noVariants = Product::create(['name' => 'No variants', 'slug' => 'no-variants', 'is_active' => true]);

        Livewire::test(ListProducts::class)
            ->filterTable('stock_status', 'in_stock')
            ->assertCanSeeTableRecords([$inStock, $lowStock])
            ->assertCanNotSeeTableRecords([$outOfStock, $noVariants]);

        Livewire::test(ListProducts::class)
            ->filterTable('stock_status', 'low_stock')
            ->assertCanSeeTableRecords([$lowStock])
            ->assertCanNotSeeTableRecords([$inStock, $outOfStock, $noVariants]);

        Livewire::test(ListProducts::class)
            ->filterTable('stock_status', 'out_of_stock')
            ->assertCanSeeTableRecords([$outOfStock, $noVariants])
            ->assertCanNotSeeTableRecords([$inStock, $lowStock]);
    }

    public function test_price_range_filter_limits_records(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $cheap = $this->product('Cheap', 'cheap', ['wholesale_price' => 10]);
        $mid = $this->product('Mid', 'mid', ['wholesale_price' => 30]);
        $expensive = $this->product('Expensive', 'expensive', ['wholesale_price' => 80]);

        Livewire::test(ListProducts::class)
            ->filterTable('price', ['min' => 20, 'max' => 60])
            ->assertCanSeeTableRecords([$mid])
            ->assertCanNotSeeTableRecords([$cheap, $expensive]);
    }

    public function test_published_and_has_variants_filters(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $published = $this->product('Published', 'published', attributes: ['published_at' => now()]);
        $unpublished = $this->product('Unpublished', 'unpublished', attributes: ['published_at' => null]);

        Livewire::test(ListProducts::class)
            ->filterTable('published', true)
            ->assertCanSeeTableRecords([$published])
            ->assertCanNotSeeTableRecords([$unpublished]);

        $noVariants = Product::create(['name' => 'No variants', 'slug' => 'no-variants', 'is_active' => true]);

        Livewire::test(ListProducts::class)
            ->filterTable('has_variants', false)
            ->assertCanSeeTableRecords([$noVariants])
            ->assertCanNotSeeTableRecords([$published, $unpublished]);
    }
}
