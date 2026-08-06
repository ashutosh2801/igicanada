<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CustomerAddress;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewOrderForAdmin;
use App\Notifications\OrderSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('canada-post.enabled', true);
        config()->set('canada-post.api_key', 'test-key');
        config()->set('canada-post.api_secret', 'test-secret');
        config()->set('canada-post.origin_postal_code', 'L4W2S1');
        Http::fake([
            'ct.soa-gw.canadapost.ca/*' => Http::response($this->rateResponse(), 200, [
                'Content-Type' => 'application/vnd.cpc.ship.rate-v4+xml',
            ]),
        ]);
    }

    public function test_checkout_creates_an_immutable_order_snapshot_and_clears_cart(): void
    {
        Notification::fake();
        config()->set('commerce.order_notification_email', 'orders@igicanada.test');
        [$user, $cart, $variant, $address] = $this->checkoutFixture();

        $this->actingAs($user)->post('/checkout', [
            'address_id' => $address->id,
            'address' => '10 King Street',
            'city' => 'Toronto',
            'province' => 'Ontario',
            'country' => 'Canada',
            'postal_code' => 'M5H 1A1',
            'phone' => '416-555-0100',
            'notes' => 'Call before delivery.',
            'shipping_service_code' => 'STANDARD',
            'payment_option' => 'enquiry',
        ])->assertRedirect();

        $order = $user->orders()->with('items')->firstOrFail();
        $this->assertStringStartsWith('IGI-', $order->order_number);
        $this->assertSame('wholesale', $order->sales_channel);
        $this->assertSame('awaiting_quote', $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('24.00', $order->subtotal);
        $this->assertSame('18.95', $order->shipping_total);
        $this->assertSame('42.95', $order->total);
        $this->assertSame('Standard Shipping', $order->shipping_method);
        $this->assertSame('Up to $100', $order->shipping_service);
        $this->assertSame('enquiry', $order->payment_method);
        $this->assertSame('10 King Street', $order->shipping_address['address']);
        $this->assertSame('8.00', $order->items->first()->unit_price);
        $this->assertSame(3, $order->items->first()->quantity);
        $this->assertSame($variant->id, $order->items->first()->product_variant_id);
        $this->assertSame(0, $cart->items()->count());
        Notification::assertSentTo($user, OrderSubmitted::class);
        Notification::assertSentOnDemand(NewOrderForAdmin::class);
    }

    public function test_customer_cannot_use_another_accounts_saved_address(): void
    {
        [$user] = $this->checkoutFixture();
        $other = User::factory()->create();
        $address = CustomerAddress::create([
            'user_id' => $other->id,
            'type' => 'primary',
            'address_line1' => 'Other address',
        ]);

        $this->actingAs($user)->post('/checkout', [
            'address_id' => $address->id,
            'address' => 'Other address',
            'city' => 'Toronto',
            'province' => 'Ontario',
            'country' => 'Canada',
            'postal_code' => 'M5H 1A1',
            'phone' => '416-555-0100',
            'shipping_service_code' => 'STANDARD',
            'payment_option' => 'enquiry',
        ])->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_can_download_own_invoice_pdf_only(): void
    {
        Notification::fake();
        [$user, $cart, $variant, $address] = $this->checkoutFixture();
        $this->actingAs($user)->post('/checkout', [
            'address_id' => $address->id,
            'address' => '10 King Street',
            'city' => 'Toronto',
            'province' => 'Ontario',
            'country' => 'Canada',
            'postal_code' => 'M5H 1A1',
            'phone' => '416-555-0100',
            'shipping_service_code' => 'STANDARD',
            'payment_option' => 'enquiry',
        ]);
        $order = $user->orders()->firstOrFail();

        $response = $this->actingAs($user)->get("/orders/{$order->id}/invoice");
        $response->assertSuccessful()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', (string) $response->getContent());

        $other = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'approved',
        ]);
        $this->actingAs($other)->get("/orders/{$order->id}/invoice")->assertNotFound();
    }

    public function test_checkout_returns_the_legacy_standard_shipping_charge_for_the_cart(): void
    {
        [$user] = $this->checkoutFixture();

        $this->actingAs($user)->postJson('/checkout/shipping-rates', [
            'country' => 'Canada',
            'postal_code' => 'M5H 1A1',
        ])->assertOk()->assertJsonPath('rates.0.code', 'STANDARD')
            ->assertJsonPath('rates.0.price', '18.95');
    }

    public function test_paypal_checkout_creates_payment_redirects_and_emails_customer_and_admin(): void
    {
        Notification::fake();
        config()->set('commerce.order_notification_email', 'orders@igicanada.test');
        config()->set('services.paypal', [
            'mode' => 'sandbox',
            'client_id' => 'sandbox-client',
            'client_secret' => 'sandbox-secret',
            'webhook_id' => 'WEBHOOK-1',
        ]);
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'access-token', 'expires_in' => 3600]),
            '*/v2/checkout/orders' => Http::response([
                'id' => 'CHECKOUT-PAYPAL-1',
                'status' => 'CREATED',
                'links' => [['rel' => 'approve', 'href' => 'https://sandbox.paypal.test/approve/checkout-1']],
            ], 201),
        ]);
        [$user, $cart, $variant, $address] = $this->checkoutFixture();

        $this->actingAs($user)->post('/checkout', [
            'address_id' => $address->id,
            'address' => '10 King Street',
            'city' => 'Toronto',
            'province' => 'Ontario',
            'country' => 'Canada',
            'country_code' => 'CA',
            'postal_code' => 'M5H 1A1',
            'phone' => '416-555-0100',
            'shipping_service_code' => 'STANDARD',
            'payment_option' => 'paypal',
        ])->assertRedirect('https://sandbox.paypal.test/approve/checkout-1');

        $order = $user->orders()->firstOrFail();
        $this->assertSame('quoted', $order->status);
        $this->assertSame('paypal', $order->payment_method);
        $this->assertSame('18.95', $order->shipping_total);
        $this->assertSame(0, $cart->items()->count());
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider_order_id' => 'CHECKOUT-PAYPAL-1',
            'amount' => 42.95,
            'status' => 'created',
        ]);
        Notification::assertSentTo($user, OrderSubmitted::class);
        Notification::assertSentOnDemand(NewOrderForAdmin::class);
    }

    private function checkoutFixture(): array
    {
        $tier = PriceTier::create(['name' => 'Level 2', 'discount_percentage' => 20, 'is_active' => true]);
        $user = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'approved',
            'price_tier_id' => $tier->id,
        ]);
        $user->resellerProfile()->create(['company' => 'Test Company']);
        $address = $user->addresses()->create([
            'type' => 'primary',
            'address_line1' => '10 King Street',
            'city' => 'Toronto',
            'province' => 'Ontario',
            'country' => 'Canada',
            'postal_code' => 'M5H 1A1',
            'telephone' => '416-555-0100',
        ]);
        $product = Product::create([
            'sku' => 'W100', 'name' => 'Wallet', 'slug' => 'wallet',
            'visibility' => 'wholesale', 'is_active' => true, 'weight_kg' => 1,
        ]);
        $variant = $product->variants()->create([
            'wholesale_price' => 10, 'retail_price' => 20, 'stock_quantity' => 10,
            'wholesale_minimum_quantity' => 2, 'is_active' => true,
        ]);
        $cart = Cart::create(['user_id' => $user->id]);
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 3]);

        return [$user, $cart, $variant, $address];
    }

    private function rateResponse(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<price-quotes xmlns="http://www.canadapost.ca/ws/ship/rate-v4">
  <price-quote>
    <service-code>DOM.EP</service-code>
    <service-name>Expedited Parcel</service-name>
    <price-details><due>12.34</due></price-details>
    <service-standard>
      <expected-transit-time>2</expected-transit-time>
      <expected-delivery-date>2026-08-05</expected-delivery-date>
    </service-standard>
  </price-quote>
</price-quotes>
XML;
    }
}
