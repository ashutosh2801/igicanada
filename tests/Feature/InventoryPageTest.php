<?php

namespace Tests\Feature;

use App\Filament\Resources\Inventory\Pages\ListInventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryPageTest extends TestCase
{
    use RefreshDatabase;

    private function variant(string $name, array $product = [], array $variant = []): ProductVariant
    {
        $model = Product::create(array_merge([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'visibility' => 'both',
            'is_active' => true,
        ], $product));

        return $model->variants()->create(array_merge([
            'wholesale_price' => 20,
            'retail_price' => 49,
            'stock_quantity' => 10,
            'is_active' => true,
        ], $variant));
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]), 'admin');
    }

    public function test_sku_falls_back_to_product_sku_and_is_searchable(): void
    {
        $this->actingAsAdmin();

        $withOwnSku = $this->variant('Alpha', ['sku' => 'P-ALPHA'], ['sku' => 'V-ALPHA-RED']);
        $withProductSku = $this->variant('Beta', ['sku' => 'P-BETA'], ['sku' => null]);

        Livewire::test(ListInventory::class)
            ->assertTableColumnStateSet('sku', 'V-ALPHA-RED', $withOwnSku)
            ->assertTableColumnStateSet('sku', 'P-BETA', $withProductSku)
            ->searchTable('P-BETA')
            ->assertCanSeeTableRecords([$withProductSku])
            ->assertCanNotSeeTableRecords([$withOwnSku]);
    }

    public function test_sku_column_sorts_ascending_and_descending_using_displayed_sku(): void
    {
        $this->actingAsAdmin();

        $b = $this->variant('Bravo', ['sku' => 'P-ZZZ'], ['sku' => 'B-100']);
        $a = $this->variant('Alpha', ['sku' => 'A-200'], ['sku' => null]); // falls back to product SKU
        $c = $this->variant('Charlie', ['sku' => 'P-AAA'], ['sku' => 'C-300']);

        Livewire::test(ListInventory::class)
            ->sortTable('sku')
            ->assertCanSeeTableRecords([$a, $b, $c], inOrder: true)
            ->sortTable('sku', 'desc')
            ->assertCanSeeTableRecords([$c, $b, $a], inOrder: true);
    }

    public function test_products_list_sorts_by_sku(): void
    {
        $this->actingAsAdmin();
        \Filament\Facades\Filament::setCurrentPanel('admin');

        $b = Product::create(['name' => 'B', 'slug' => 'b', 'sku' => 'SKU-B', 'visibility' => 'both', 'is_active' => true]);
        $a = Product::create(['name' => 'A', 'slug' => 'a', 'sku' => 'SKU-A', 'visibility' => 'both', 'is_active' => true]);
        $c = Product::create(['name' => 'C', 'slug' => 'c', 'sku' => 'SKU-C', 'visibility' => 'both', 'is_active' => true]);

        Livewire::test(\App\Filament\Resources\Products\Pages\ListProducts::class)
            ->sortTable('sku')
            ->assertCanSeeTableRecords([$a, $b, $c], inOrder: true)
            ->sortTable('sku', 'desc')
            ->assertCanSeeTableRecords([$c, $b, $a], inOrder: true);
    }

    public function test_unpublished_products_are_shown_as_unpublished(): void
    {
        $this->actingAsAdmin();

        $published = $this->variant('Published wallet');
        $unpublished = $this->variant('Hidden wallet', ['is_active' => false]);

        Livewire::test(ListInventory::class)
            ->assertTableColumnStateSet('publish_status', 'published', $published)
            ->assertTableColumnStateSet('publish_status', 'unpublished', $unpublished)
            ->filterTable('published', false)
            ->assertCanSeeTableRecords([$unpublished])
            ->assertCanNotSeeTableRecords([$published]);
    }

    public function test_rows_keep_their_position_after_stock_is_saved(): void
    {
        $this->actingAsAdmin();

        $a = $this->variant('Apple', variant: ['stock_quantity' => 1]);
        $b = $this->variant('Banana', variant: ['stock_quantity' => 50]);
        $c = $this->variant('Cherry', variant: ['stock_quantity' => 5]);

        $listing = Livewire::test(ListInventory::class)
            ->assertCanSeeTableRecords([$a, $b, $c], inOrder: true)
            ->set('pendingStockUpdates', [$a->getKey() => 500])
            ->callTableAction('saveStockUpdates')
            ->assertCanSeeTableRecords([$a, $b, $c], inOrder: true);

        $this->assertSame(500, $a->fresh()->stock_quantity);
    }
}
