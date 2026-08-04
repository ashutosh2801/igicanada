<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalService
{
    public function configured(): bool
    {
        return filled(config('services.paypal.client_id')) && filled(config('services.paypal.client_secret'));
    }

    public function startCheckout(Order $order): string
    {
        $existing = $order->payments()
            ->where('provider', 'paypal')
            ->where('status', 'created')
            ->where('amount', $order->total)
            ->where('currency', $order->currency)
            ->where('created_at', '>=', now()->subHours(2))
            ->latest()
            ->first();

        if ($existing && $approvalUrl = data_get($existing->provider_metadata, 'approval_url')) {
            return $approvalUrl;
        }

        $remote = $this->createOrder($order);
        $payment = $order->payments()->create([
            'provider' => 'paypal',
            'provider_order_id' => $remote['id'],
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => 'created',
            'provider_metadata' => ['approval_url' => $remote['approval_url']],
        ]);

        return (string) data_get($payment->provider_metadata, 'approval_url');
    }

    public function createOrder(Order $order): array
    {
        $response = $this->client()
            ->withHeaders(['PayPal-Request-Id' => 'igi-create-'.$order->id.'-'.$order->updated_at->timestamp])
            ->post($this->baseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->order_number,
                    'custom_id' => (string) $order->id,
                    'description' => "IGI Canada order {$order->order_number}",
                    'amount' => [
                        'currency_code' => $order->currency,
                        'value' => $order->total,
                    ],
                ]],
                'application_context' => [
                    'brand_name' => 'IGI Canada',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => route('paypal.return'),
                    'cancel_url' => route('paypal.cancel'),
                ],
            ]);

        $response->throw();
        $data = $response->json();
        $approvalUrl = collect($data['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;
        if (! isset($data['id']) || ! $approvalUrl) {
            throw new RuntimeException('PayPal did not return an order approval URL.');
        }

        return ['id' => $data['id'], 'status' => $data['status'] ?? 'CREATED', 'approval_url' => $approvalUrl];
    }

    public function captureOrder(string $providerOrderId, int $paymentId): array
    {
        $response = $this->client()
            ->withHeaders(['PayPal-Request-Id' => 'igi-capture-'.$paymentId])
            ->withBody('{}', 'application/json')
            ->post($this->baseUrl()."/v2/checkout/orders/{$providerOrderId}/capture");

        $response->throw();

        return $response->json();
    }

    public function verifyWebhook(Request $request): bool
    {
        $webhookId = config('services.paypal.webhook_id');
        if (! filled($webhookId)) {
            throw new RuntimeException('PayPal webhook ID is not configured.');
        }

        $headers = [
            'transmission_id' => $request->header('paypal-transmission-id'),
            'transmission_time' => $request->header('paypal-transmission-time'),
            'transmission_sig' => $request->header('paypal-transmission-sig'),
            'cert_url' => $request->header('paypal-cert-url'),
            'auth_algo' => $request->header('paypal-auth-algo'),
        ];
        if (collect($headers)->contains(fn ($value) => blank($value))) {
            return false;
        }

        $response = $this->client()->post($this->baseUrl().'/v1/notifications/verify-webhook-signature', [
            ...$headers,
            'webhook_id' => $webhookId,
            'webhook_event' => $request->json()->all(),
        ]);
        $response->throw();

        return $response->json('verification_status') === 'SUCCESS';
    }

    private function client(): PendingRequest
    {
        if (! $this->configured()) {
            throw new RuntimeException('PayPal credentials are not configured.');
        }

        return Http::acceptJson()->asJson()->withToken($this->accessToken())->timeout(20)->retry(2, 250);
    }

    private function accessToken(): string
    {
        $cacheKey = 'paypal-access-token-'.sha1((string) config('services.paypal.client_id'));

        return Cache::remember($cacheKey, now()->addMinutes(8), function (): string {
            $response = Http::asForm()
                ->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.client_secret'))
                ->timeout(20)
                ->post($this->baseUrl().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);
            $response->throw();

            return $response->json('access_token')
                ?? throw new RuntimeException('PayPal did not return an access token.');
        });
    }

    private function baseUrl(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }
}
