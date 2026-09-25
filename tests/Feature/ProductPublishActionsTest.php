<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductPublishActionsTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $name, string $slug, bool $isActive = true): Product
    {
        $product = Product::create([
            'name' => $name,
            'slug' => $slug,
            'visibility' => 'both',
            'is_active' => $isActive,
            'published_at' => now()->subDay(),
        ]);

        $product->variants()->create([
            'sku' => strtoupper($slug),
            'wholesale_price' => 20,
            'retail_price' => 49,
            'stock_quantity' => 10,
            'is_available_wholesale' => true,
            'is_available_retail' => true,
            'is_active' => true,
        ]);

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

    public function test_row_unpublish_hides_product_from_all_sites_but_keeps_publish_date(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $product = $this->product('Holiday Belt', 'holiday-belt');

        Livewire::test(ListProducts::class)
            ->callTableAction('togglePublish', $product);

        $fresh = $product->fresh();
        $this->assertFalse((bool) $fresh->is_active);
        $this->assertNotNull($fresh->published_at);
    }

    public function test_row_publish_makes_product_visible_again(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $product = $this->product('Back From Holidays', 'back-from-holidays', isActive: false);

        Livewire::test(ListProducts::class)
            ->callTableAction('togglePublish', $product);

        $this->assertTrue((bool) $product->fresh()->is_active);
    }

    public function test_page_header_unpublish_works_on_edit_page(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $product = $this->product('Edit Page Belt', 'edit-page-belt');

        Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->callAction('togglePublish');

        $this->assertFalse((bool) $product->fresh()->is_active);
    }

    public function test_bulk_unpublish_hides_all_selected_products(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $a = $this->product('A', 'a');
        $b = $this->product('B', 'b');

        Livewire::test(ListProducts::class)
            ->callTableBulkAction('unpublishFromAllWebsites', [$a, $b]);

        $this->assertFalse((bool) $a->fresh()->is_active);
        $this->assertFalse((bool) $b->fresh()->is_active);
    }

    public function test_bulk_unpublish_skips_already_inactive_products(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $active = $this->product('Active', 'active');
        $inactive = $this->product('Inactive', 'inactive', isActive: false);

        Livewire::test(ListProducts::class)
            ->callTableBulkAction('unpublishFromAllWebsites', [$active, $inactive]);

        $this->assertFalse((bool) $active->fresh()->is_active);
        $this->assertFalse((bool) $inactive->fresh()->is_active);
    }

    public function test_bulk_publish_makes_selected_products_visible_again(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $a = $this->product('A', 'a', isActive: false);
        $b = $this->product('B', 'b', isActive: false);

        Livewire::test(ListProducts::class)
            ->callTableBulkAction('publishToAllWebsites', [$a, $b]);

        $this->assertTrue((bool) $a->fresh()->is_active);
        $this->assertTrue((bool) $b->fresh()->is_active);
    }

    public function test_actions_are_labeled_unpublish_or_publish_based_on_state(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $active = $this->product('Active', 'active');
        $inactive = $this->product('Inactive', 'inactive', isActive: false);

        $listing = Livewire::test(ListProducts::class);

        $table = $listing->instance()->getTable();
        $activeAction = $table->getAction('togglePublish');
        $inactiveAction = $table->getAction('togglePublish');

        $activeAction->record($active);
        $this->assertSame('Unpublish', $activeAction->getLabel());

        $inactiveAction->record($inactive);
        $this->assertSame('Publish', $inactiveAction->getLabel());
    }
}