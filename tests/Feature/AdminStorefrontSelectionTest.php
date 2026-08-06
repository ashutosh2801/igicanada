<?php

namespace Tests\Feature;

use App\Filament\Resources\ContactEnquiries\ContactEnquiryResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\ContactEnquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\AdminStorefront;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStorefrontSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_selects_a_store_on_first_panel_visit_and_choice_is_remembered(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertRedirect(route('admin.storefront.select'));

        $this->get(route('admin.storefront.select'))
            ->assertSuccessful()
            ->assertSeeText('Which store would you like to manage?')
            ->assertSeeText('All Stores')
            ->assertSeeText('IGI Canada · Wholesale')
            ->assertSeeText('Leather Wallets · Retail');

        $this->post(route('admin.storefront.store'), [
            'sales_channel' => 'retail',
            'initial_selection' => true,
        ])->assertRedirect('/admin');

        $this->assertSame('retail', $admin->fresh()->admin_sales_channel);
        $this->assertSame('retail', session(AdminStorefront::SESSION_KEY));
        $this->get('/admin')->assertSuccessful()->assertSeeText('Leather Wallets · Retail');
    }

    public function test_remembered_store_opens_panel_without_asking_again(): void
    {
        $admin = $this->admin('wholesale');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertSuccessful()
            ->assertSeeText('IGI Canada · Wholesale');

        $this->assertSame('wholesale', session(AdminStorefront::SESSION_KEY));
    }

    public function test_selected_store_filters_shared_admin_resources(): void
    {
        $this->actingAs($this->admin('retail'))
            ->withSession([AdminStorefront::SESSION_KEY => 'retail']);

        $retailProduct = $this->product('retail-product', 'retail');
        $bothProduct = $this->product('both-product', 'both');
        $this->product('wholesale-product', 'wholesale');

        $retailOrder = $this->order('RETAIL-1', 'retail');
        $this->order('WHOLESALE-1', 'wholesale');

        $retailCustomer = User::factory()->create(['account_type' => 'retail']);
        User::factory()->create(['account_type' => 'wholesale']);

        $retailEnquiry = $this->enquiry('retail@example.com', 'retail');
        $this->enquiry('wholesale@example.com', 'wholesale');

        $this->assertEqualsCanonicalizing(
            [$retailProduct->id, $bothProduct->id],
            ProductResource::getEloquentQuery()->pluck('id')->all(),
        );
        $this->assertSame([$retailOrder->id], OrderResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$retailCustomer->id], UserResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$retailEnquiry->id], ContactEnquiryResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_invalid_store_selection_is_rejected(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.storefront.store'), ['sales_channel' => 'unknown'])
            ->assertSessionHasErrors('sales_channel');
    }

    private function admin(?string $channel = null): User
    {
        return User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => $channel,
        ]);
    }

    private function product(string $slug, string $visibility): Product
    {
        return Product::create([
            'name' => str($slug)->headline(),
            'slug' => $slug,
            'visibility' => $visibility,
            'is_active' => true,
        ]);
    }

    private function order(string $number, string $channel): Order
    {
        return Order::create([
            'order_number' => $number,
            'sales_channel' => $channel,
            'shipping_address' => [],
            'subtotal' => 10,
            'total' => 10,
            'placed_at' => now(),
        ]);
    }

    private function enquiry(string $email, string $channel): ContactEnquiry
    {
        return ContactEnquiry::create([
            'sales_channel' => $channel,
            'name' => 'Customer',
            'email' => $email,
            'message' => 'Question',
        ]);
    }
}
