<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::clear();
        config()->set('services.paypal', [
            'mode' => 'sandbox',
            'client_id' => 'sandbox-client',
            'client_secret' => 'sandbox-secret',
            'webhook_id' => 'WEBHOOK-1',
        ]);
    }

    public function test_quoted_order_creates_server_calculated_paypal_order(): void
    {
        [$user, $order] = $this->quotedOrder();
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'access-token', 'expires_in' => 3600]),
            '*/v2/checkout/orders' => Http::response([
                'id' => 'PAYPAL-ORDER-1',
                'status' => 'CREATED',
                'links' => [['rel' => 'approve', 'href' => 'https://sandbox.paypal.test/approve/1']],
            ], 201),
        ]);

        $this->actingAs($user)->post("/orders/{$order->id}/paypal")
            ->assertRedirect('https://sandbox.paypal.test/approve/1');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'provider_order_id' => 'PAYPAL-ORDER-1',
            'amount' => 125.50,
            'currency' => 'CAD',
            'status' => 'created',
        ]);
        Http::assertSent(fn ($request) => $request->url() === 'https://api-m.sandbox.paypal.com/v2/checkout/orders'
            && $request['purchase_units'][0]['amount']['value'] === '125.50');
    }

    public function test_completed_capture_marks_payment_and_order_paid_idempotently(): void
    {
        [$user, $order] = $this->quotedOrder();
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'paypal',
            'provider_order_id' => 'PAYPAL-ORDER-2',
            'amount' => '125.50',
            'currency' => 'CAD',
            'status' => 'created',
        ]);
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'access-token']),
            '*/v2/checkout/orders/PAYPAL-ORDER-2/capture' => Http::response([
                'id' => 'PAYPAL-ORDER-2',
                'status' => 'COMPLETED',
                'payer' => ['email_address' => 'payer@example.com'],
                'purchase_units' => [[
                    'payments' => ['captures' => [[
                        'id' => 'CAPTURE-2',
                        'status' => 'COMPLETED',
                        'amount' => ['value' => '125.50', 'currency_code' => 'CAD'],
                    ]]],
                ]],
            ]),
        ]);

        $this->actingAs($user)->get('/payments/paypal/return?token=PAYPAL-ORDER-2')
            ->assertRedirect("/orders/{$order->id}");

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'provider_capture_id' => 'CAPTURE-2',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'payment_method' => 'paypal',
            'status' => 'processing',
        ]);

        Http::fake([]);
        $this->actingAs($user)->get('/payments/paypal/return?token=PAYPAL-ORDER-2')
            ->assertRedirect("/orders/{$order->id}");
    }

    public function test_capture_amount_mismatch_never_marks_order_paid(): void
    {
        [$user, $order] = $this->quotedOrder();
        Payment::create([
            'order_id' => $order->id,
            'provider' => 'paypal',
            'provider_order_id' => 'PAYPAL-ORDER-3',
            'amount' => '125.50',
            'currency' => 'CAD',
            'status' => 'created',
        ]);
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'access-token']),
            '*/v2/checkout/orders/PAYPAL-ORDER-3/capture' => Http::response([
                'status' => 'COMPLETED',
                'purchase_units' => [['payments' => ['captures' => [[
                    'id' => 'CAPTURE-3',
                    'status' => 'COMPLETED',
                    'amount' => ['value' => '1.00', 'currency_code' => 'CAD'],
                ]]]]],
            ]),
        ]);

        $this->actingAs($user)->get('/payments/paypal/return?token=PAYPAL-ORDER-3')
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'unpaid']);
        $this->assertDatabaseMissing('payments', ['provider_order_id' => 'PAYPAL-ORDER-3', 'status' => 'completed']);
    }

    public function test_verified_webhook_completes_payment_and_duplicate_is_idempotent(): void
    {
        [$user, $order] = $this->quotedOrder();
        Payment::create([
            'order_id' => $order->id,
            'provider' => 'paypal',
            'provider_order_id' => 'PAYPAL-WEBHOOK-1',
            'amount' => '125.50',
            'currency' => 'CAD',
            'status' => 'created',
        ]);
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'access-token']),
            '*/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
        ]);
        $payload = [
            'id' => 'EVENT-1',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAPTURE-WEBHOOK-1',
                'amount' => ['value' => '125.50', 'currency_code' => 'CAD'],
                'supplementary_data' => ['related_ids' => ['order_id' => 'PAYPAL-WEBHOOK-1']],
            ],
        ];
        $headers = [
            'paypal-transmission-id' => 'transmission-1',
            'paypal-transmission-time' => '2026-07-31T12:00:00Z',
            'paypal-transmission-sig' => 'signature',
            'paypal-cert-url' => 'https://api.paypal.com/cert',
            'paypal-auth-algo' => 'SHA256withRSA',
        ];

        $this->withHeaders($headers)->postJson('/payments/paypal/webhook', $payload)->assertOk();
        $this->withHeaders($headers)->postJson('/payments/paypal/webhook', $payload)->assertOk();

        $this->assertDatabaseHas('payments', [
            'provider_order_id' => 'PAYPAL-WEBHOOK-1',
            'provider_capture_id' => 'CAPTURE-WEBHOOK-1',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
        $this->assertDatabaseCount('webhook_events', 1);
        Http::assertSentCount(2);
    }

    private function quotedOrder(): array
    {
        $user = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'approved',
        ]);
        $order = Order::create([
            'order_number' => 'IGI-TEST-'.fake()->unique()->numberBetween(1000, 9999),
            'user_id' => $user->id,
            'shipping_address' => ['name' => $user->name],
            'currency' => 'CAD',
            'status' => 'quoted',
            'payment_status' => 'unpaid',
            'payment_method' => 'invoice',
            'subtotal' => '100.00',
            'shipping_total' => '12.00',
            'tax_total' => '13.50',
            'total' => '125.50',
            'placed_at' => now(),
        ]);

        return [$user, $order];
    }
}
