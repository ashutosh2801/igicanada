<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductsBulkUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $name, string $slug, array $variant = []): Product
    {
        $product = Product::create([
            'name' => $name,
            'slug' => $slug,
            'visibility' => 'both',
            'is_active' => true,
        ]);

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

    private function admin(): User
    {
        return User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);
    }

    public function test_bulk_update_sets_prices_for_selected_products(): void
    {
        $this->actingAs($this->admin());

        $a = $this->product('A', 'a');
        $b = $this->product('B', 'b');

        Livewire::test(ListProducts::class)
            ->callTableBulkAction('updatePrices', [$a, $b], [
                'price_type' => 'both',
                'operation' => 'set',
                'value' => 25,
            ])
            ->assertHasNoFormErrors();

        $this->assertSame('25.00', $a->variants->first()->fresh()->wholesale_price);
        $this->assertSame('25.00', $a->variants->first()->fresh()->retail_price);
        $this->assertSame('25.00', $b->variants->first()->fresh()->wholesale_price);
        $this->assertSame('25.00', $b->variants->first()->fresh()->retail_price);
    }

    public function test_bulk_update_increases_prices_by_percentage(): void
    {
        $this->actingAs($this->admin());

        $a = $this->product('A', 'a');

        Livewire::test(ListProducts::class)
            ->callTableBulkAction('updatePrices', [$a], [
                'price_type' => 'wholesale',
                'operation' => 'increase_percent',
                'value' => 10,
            ])
            ->assertHasNoFormErrors();

        $this->assertSame('22.00', $a->variants->first()->fresh()->wholesale_price);
    }

    public function test_bulk_update_stock_sets_quantity_for_selected_products(): void
    {
        $this->actingAs($this->admin());

        $a = $this->product('A', 'a');
        $b = $this->product('B', 'b');

        Livewire::test(ListProducts::class)
            ->callTableBulkAction('updateStock', [$a, $b], [
                'operation' => 'set',
                'quantity' => 5,
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(5, $a->variants->first()->fresh()->stock_quantity);
        $this->assertSame(5, $b->variants->first()->fresh()->stock_quantity);
    }

    public function test_bulk_update_stock_adds_quantity_for_selected_products(): void
    {
        $this->actingAs($this->admin());

        $a = $this->product('A', 'a');

        Livewire::test(ListProducts::class)
            ->callTableBulkAction('updateStock', [$a], [
                'operation' => 'increase',
                'quantity' => 4,
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(14, $a->variants->first()->fresh()->stock_quantity);
    }

    public function test_bulk_edit_selected_lets_user_type_price_and_stock_per_variant(): void
    {
        $this->actingAs($this->admin());

        $a = $this->product('A', 'a');
        $b = $this->product('B', 'b', [
            'color' => 'Brown',
            'wholesale_price' => 30,
            'retail_price' => 60,
            'stock_quantity' => 3,
        ]);

        $rows = [$a->variants->first(), $b->variants->first()];

        Livewire::test(ListProducts::class)
            ->callTableBulkAction('editSelected', [$a, $b], [
                'variants' => [
                    ['id' => $rows[0]->getKey(), 'wholesale_price' => 11, 'retail_price' => 22, 'stock_quantity' => 7],
                    ['id' => $rows[1]->getKey(), 'wholesale_price' => 33, 'retail_price' => 44, 'stock_quantity' => 8],
                ],
            ])
            ->assertHasNoFormErrors();

        $this->assertSame('11.00', $rows[0]->fresh()->wholesale_price);
        $this->assertSame('22.00', $rows[0]->fresh()->retail_price);
        $this->assertSame(7, $rows[0]->fresh()->stock_quantity);
        $this->assertSame('33.00', $rows[1]->fresh()->wholesale_price);
        $this->assertSame('44.00', $rows[1]->fresh()->retail_price);
        $this->assertSame(8, $rows[1]->fresh()->stock_quantity);
    }

    public function test_bulk_edit_selected_only_updates_fields_visible_in_current_storefront(): void
    {
        $this->actingAs($this->admin());

        $a = $this->product('A', 'a');
        $row = $a->variants->first();

        session(['admin_sales_channel' => 'retail']);

        Livewire::test(ListProducts::class)
            ->callTableBulkAction('editSelected', [$a], [
                'variants' => [
                    ['id' => $row->getKey(), 'retail_price' => 77, 'stock_quantity' => 2],
                ],
            ])
            ->assertHasNoFormErrors();

        $this->assertSame('20.00', $row->fresh()->wholesale_price);
        $this->assertSame('77.00', $row->fresh()->retail_price);
        $this->assertSame(2, $row->fresh()->stock_quantity);
    }
}
