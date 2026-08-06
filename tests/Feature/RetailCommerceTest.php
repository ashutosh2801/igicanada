<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Notifications\NewOrderForAdmin;
use App\Notifications\RetailOrderSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RetailCommerceTest extends TestCase
{
    use RefreshDatabase;

    public function test_retail_customer_can_browse_a_product_and_manage_a_guest_cart(): void
    {
        [, $variant] = $this->retailProduct();

        $this->get('https://leatherwallets.ca/products/slim-wallet')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalogue/Show')
                ->where('product.name', 'Retail Slim Wallet')
                ->where('product.variants.0.price', '49.99'));

        $this->post('https://leatherwallets.ca/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->get('https://leatherwallets.ca/cart')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cart/Index')
                ->has('items', 1)
                ->where('items.0.quantity', 2)
                ->where('items.0.unitPrice', '49.99')
                ->where('subtotal', '99.98')
                ->where('retailStorefront.cartCount', 2));
    }

    public function test_guest_checkout_creates_a_retail_order_snapshot_and_reduces_stock(): void
    {
        Notification::fake();
        config()->set('services.paypal.client_id', null);
        config()->set('services.paypal.client_secret', null);
        config()->set('commerce.order_notification_email', 'orders@example.com');
        [, $variant] = $this->retailProduct();

        $this->post('https://leatherwallets.ca/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->post('https://leatherwallets.ca/checkout', $this->checkoutData())
            ->assertRedirect();

        $order = Order::query()->with('items')->sole();
        $this->assertStringStartsWith('LW-', $order->order_number);
        $this->assertSame('retail', $order->sales_channel);
        $this->assertNull($order->user_id);
        $this->assertSame('customer@example.com', $order->customer_email);
        $this->assertSame('manual', $order->payment_method);
        $this->assertSame('99.98', $order->subtotal);
        $this->assertSame('18.95', $order->shipping_total);
        $this->assertSame('118.93', $order->total);
        $this->assertSame('49.99', $order->items->first()->unit_price);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame(8, $variant->fresh()->stock_quantity);
        $this->assertDatabaseCount('cart_items', 0);

        $this->get('https://leatherwallets.ca/orders/'.$order->id)
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Orders/Show')
                ->where('order.number', $order->order_number)
                ->where('order.total', '118.93'));

        Notification::assertSentOnDemand(RetailOrderSubmitted::class);
        Notification::assertSentOnDemand(NewOrderForAdmin::class);
    }

    public function test_retail_cart_rejects_wholesale_only_or_retail_disabled_variants(): void
    {
        $product = Product::create([
            'name' => 'Wholesale Wallet',
            'slug' => 'wholesale-wallet',
            'visibility' => 'wholesale',
            'is_active' => true,
        ]);
        $variant = $product->variants()->create([
            'retail_price' => 40,
            'stock_quantity' => 10,
            'is_available_retail' => false,
            'is_active' => true,
        ]);

        $this->post('https://leatherwallets.ca/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertNotFound();
    }

    public function test_retail_checkout_uses_retail_paypal_return_urls(): void
    {
        Notification::fake();
        config()->set('services.paypal', [
            'mode' => 'sandbox',
            'client_id' => 'retail-client',
            'client_secret' => 'retail-secret',
            'webhook_id' => 'retail-webhook',
        ]);
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'retail-token', 'expires_in' => 3600]),
            '*/v2/checkout/orders' => Http::response([
                'id' => 'RETAIL-PAYPAL-1',
                'status' => 'CREATED',
                'links' => [['rel' => 'approve', 'href' => 'https://sandbox.paypal.test/retail-approval']],
            ], 201),
        ]);
        [, $variant] = $this->retailProduct();

        $this->post('https://leatherwallets.ca/cart/items', ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->post('https://leatherwallets.ca/checkout', $this->checkoutData())
            ->assertRedirect('https://sandbox.paypal.test/retail-approval');

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v2/checkout/orders')
            && data_get($request->data(), 'application_context.brand_name') === 'Leather Wallets Canada'
            && data_get($request->data(), 'application_context.return_url') === 'https://leatherwallets.ca/payments/paypal/return'
            && data_get($request->data(), 'application_context.cancel_url') === 'https://leatherwallets.ca/payments/paypal/cancel');

        $this->assertDatabaseHas('orders', ['sales_channel' => 'retail', 'payment_method' => 'paypal']);
        $this->assertDatabaseHas('payments', ['provider_order_id' => 'RETAIL-PAYPAL-1']);
    }

    public function test_registered_store_charges_destination_hst_on_products_and_shipping(): void
    {
        Notification::fake();
        config()->set('services.paypal.client_id', null);
        config()->set('services.paypal.client_secret', null);
        config()->set('retail-tax.gst_hst_registered', true);
        config()->set('retail-tax.registration_number', '123456789RT0001');
        [, $variant] = $this->retailProduct();

        $this->post('https://leatherwallets.ca/cart/items', ['variant_id' => $variant->id, 'quantity' => 2]);
        $this->post('https://leatherwallets.ca/checkout', $this->checkoutData())->assertRedirect();

        $order = Order::query()->sole();
        $this->assertSame('15.46', $order->tax_total);
        $this->assertSame('134.39', $order->total);
        $this->assertSame('HST', $order->tax_breakdown['label']);
        $this->assertSame(0.13, $order->tax_breakdown['rate']);
        $this->assertSame('123456789RT0001', $order->tax_breakdown['registration_number']);
    }

    private function retailProduct(): array
    {
        $product = Product::create([
            'name' => 'Shared Slim Wallet',
            'retail_name' => 'Retail Slim Wallet',
            'slug' => 'slim-wallet',
            'visibility' => 'both',
            'is_active' => true,
            'published_at' => now(),
        ]);
        $variant = $product->variants()->create([
            'sku' => 'RTL-SLIM-1',
            'retail_price' => 49.99,
            'retail_compare_at_price' => 59.99,
            'wholesale_price' => 24,
            'stock_quantity' => 10,
            'is_available_retail' => true,
            'is_available_wholesale' => true,
            'is_active' => true,
        ]);

        return [$product, $variant];
    }

    private function checkoutData(): array
    {
        return [
            'name' => 'Retail Customer',
            'email' => 'customer@example.com',
            'phone' => '416-555-0100',
            'address' => '10 King Street',
            'city' => 'Toronto',
            'province' => 'Ontario',
            'country' => 'Canada',
            'country_code' => 'CA',
            'postal_code' => 'M5H 1A1',
            'notes' => 'Leave at the front desk.',
        ];
    }
}
