<?php

namespace Tests\Feature;

use App\Models\PriceTier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_reseller_can_add_valid_wholesale_quantity(): void
    {
        [$user, $variant] = $this->resellerAndVariant();

        $this->actingAs($user)->post('/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 3,
        ])->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);
        $this->actingAs($user)->get('/cart')->assertInertia(fn (Assert $page) => $page
            ->component('Cart/Index')
            ->where('items.0.option', 'Vintage Brown · Small, Medium, Large')
            ->where('items.0.unitPrice', '8.00')
            ->where('items.0.lineTotal', '24.00')
            ->where('subtotal', '24.00'));
    }

    public function test_minimum_and_stock_are_enforced_server_side(): void
    {
        [$user, $variant] = $this->resellerAndVariant();

        $this->actingAs($user)->post('/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertSessionHasErrors('quantity');

        $this->actingAs($user)->post('/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 11,
        ])->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_non_approved_account_cannot_access_cart(): void
    {
        $user = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'pending',
        ]);

        $this->actingAs($user)->get('/cart')->assertForbidden();
    }

    private function resellerAndVariant(): array
    {
        $tier = PriceTier::create(['name' => 'Level 2', 'discount_percentage' => 20, 'is_active' => true]);
        $user = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'approved',
            'price_tier_id' => $tier->id,
        ]);
        $product = Product::create([
            'sku' => 'W100',
            'name' => 'Wallet',
            'slug' => 'wallet',
            'visibility' => 'wholesale',
            'is_active' => true,
        ]);
        $variant = $product->variants()->create([
            'color' => 'Vintage Brown',
            'color_code' => '#8b4513',
            'sizes' => ['Small', 'Medium', 'Large'],
            'wholesale_price' => 10,
            'retail_price' => 20,
            'stock_quantity' => 10,
            'wholesale_minimum_quantity' => 2,
            'is_active' => true,
        ]);

        return [$user, $variant];
    }
}
