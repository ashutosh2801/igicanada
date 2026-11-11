<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CopyProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_copy_product_creates_unpublished_copy_with_zero_stock(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);
        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel('admin');

        $category = Category::create(['name' => 'Wallets', 'slug' => 'wallets']);
        $source = Product::create([
            'legacy_id' => 99,
            'name' => 'Brown Wallet',
            'slug' => 'brown-wallet',
            'sku' => 'BW-1',
            'visibility' => 'both',
            'is_active' => true,
            'published_at' => now(),
        ]);
        $source->categories()->attach($category->id, ['position' => 3]);
        $variant = $source->variants()->create([
            'legacy_id' => 501,
            'sku' => 'BW-1-BRN',
            'color' => 'Brown',
            'retail_price' => 49.99,
            'wholesale_price' => 20,
            'stock_quantity' => 25,
            'is_active' => true,
        ]);
        $source->images()->create(['path' => 'upload/a.jpg', 'position' => 1, 'product_variant_id' => $variant->id]);

        Livewire::test(ListProducts::class)
            ->callAction('copyProduct', ['product_id' => $source->id, 'name' => 'Brown Wallet (Copy)'])
            ->assertHasNoActionErrors();

        $copy = Product::where('name', 'Brown Wallet (Copy)')->with(['variants', 'images', 'categories'])->firstOrFail();

        $this->assertFalse($copy->is_active);
        $this->assertNull($copy->published_at);
        $this->assertNull($copy->legacy_id);
        $this->assertSame('brown-wallet-copy', $copy->slug);
        $this->assertSame('BW-1-COPY', $copy->sku);

        $this->assertCount(1, $copy->variants);
        $newVariant = $copy->variants->first();
        $this->assertSame(0, $newVariant->stock_quantity);
        $this->assertSame('BW-1-BRN-COPY', $newVariant->sku);
        $this->assertNull($newVariant->legacy_id);
        $this->assertSame('49.99', $newVariant->retail_price);

        $this->assertCount(1, $copy->images);
        $this->assertSame($newVariant->id, $copy->images->first()->product_variant_id);
        $this->assertSame(3, (int) $copy->categories->first()->pivot->position);

        // Original untouched.
        $this->assertTrue($source->refresh()->is_active);
        $this->assertSame(25, $variant->refresh()->stock_quantity);
    }
}
