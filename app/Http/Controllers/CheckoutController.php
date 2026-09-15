<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Notifications\NewOrderForAdmin;
use App\Notifications\OrderSubmitted;
use App\Services\PayPalService;
use App\Services\StandardShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckoutController extends Controller
{
    public function create(Request $request, PayPalService $paypal): InertiaResponse|RedirectResponse
    {
        $cart = Cart::query()->where('user_id', $request->user()->id)->with('items')->first();
        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $addresses = $request->user()->addresses()->orderByRaw("case when type = 'primary' then 0 else 1 end")->get();

        return Inertia::render('Checkout/Create', [
            'paypalConfigured' => $paypal->configured(),
            'subtotal' => number_format($this->cartSubtotal($request), 2, '.', ''),
            'addresses' => $addresses->map(fn ($address) => [
                'id' => $address->id,
                'address' => $address->address_line1,
                'city' => $address->city,
                'province' => $address->province,
                'country' => $address->country,
                'postalCode' => $address->postal_code,
                'phone' => $address->telephone ?: $address->mobile,
            ]),
        ]);
    }

    public function rates(Request $request, StandardShippingService $shipping): JsonResponse
    {
        $destination = $this->destinationRules();
        $data = $request->validate([
            ...$destination,
            'province' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $quote = $shipping->quote(
                $this->cartSubtotal($request),
                $data['country'],
                $data['country_code'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $subtotal = $this->cartSubtotal($request);
        $shippingTotal = round((float) $quote['price'], 2);
        $tax = $this->calculateWholesaleTax($subtotal + $shippingTotal, $data['country_code'] ?? '', $data['province'] ?? '');

        return response()->json(['rates' => [[
            ...$quote,
            'transitDays' => null,
            'deliveryDate' => null,
        ]], 'tax' => [
            'label' => $tax['label'],
            'amount' => number_format((float) $tax['amount'], 2, '.', ''),
            'rate' => $tax['rate'],
        ]]);
    }

    public function store(
        Request $request,
        StandardShippingService $shipping,
        PayPalService $paypal,
    ): Response {
        $data = $request->validate([
            'address_id' => ['nullable', 'integer'],
            'address' => ['required', 'string', 'max:300'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'postal_code' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'shipping_service_code' => ['required', 'in:STANDARD'],
            'payment_option' => ['required', 'in:paypal,enquiry'],
        ]);

        if ($data['payment_option'] === 'paypal' && ! $paypal->configured()) {
            throw ValidationException::withMessages(['payment_option' => 'PayPal is not configured yet. Please submit an enquiry instead.']);
        }

        $addressId = null;
        if (! empty($data['address_id'])) {
            $addressId = $request->user()->addresses()->whereKey($data['address_id'])->value('id');
            abort_unless($addressId, 422, 'The selected address does not belong to this account.');
        }

        $order = DB::transaction(function () use ($request, $data, $addressId, $shipping): Order {
            $user = $request->user()->loadMissing('priceTier');
            $cart = Cart::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if (! $cart) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $items = $cart->items()->with('variant.product')->lockForUpdate()->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $discount = (float) ($user->priceTier?->discount_percentage ?? 0);
            $subtotal = 0;
            foreach ($items as $item) {
                if (! $item->variant->is_active || $item->quantity > $item->variant->stock_quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "{$item->variant->product->name} no longer has the requested quantity.",
                    ]);
                }
                if ($item->quantity < $item->variant->wholesale_minimum_quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "{$item->variant->product->name} is below its wholesale minimum.",
                    ]);
                }
                $subtotal += $this->accountPrice((float) $item->variant->wholesale_price, $discount) * $item->quantity;
            }
            $subtotal = round($subtotal, 2);
            try {
                $shippingRate = $shipping->quote($subtotal, $data['country'], $data['country_code'] ?? null);
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['shipping_service_code' => $exception->getMessage()]);
            }
            $shippingTotal = round((float) $shippingRate['price'], 2);
            $payingOnline = $data['payment_option'] === 'paypal';
            $tax = $this->calculateWholesaleTax($subtotal + $shippingTotal, $data['country_code'] ?? '', $data['province'] ?? '');

            $order = Order::create([
                'order_number' => $this->orderNumber(),
                'sales_channel' => 'wholesale',
                'user_id' => $user->id,
                'customer_address_id' => $addressId,
                'shipping_address' => [
                    'name' => $user->name,
                    'company' => $user->resellerProfile?->company,
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'province' => $data['province'],
                    'country' => $data['country'],
                    'postal_code' => $data['postal_code'],
                    'phone' => $data['phone'],
                ],
                'currency' => 'CAD',
                'status' => $payingOnline ? 'quoted' : 'awaiting_quote',
                'payment_status' => 'unpaid',
                'payment_method' => $payingOnline ? 'paypal' : 'enquiry',
                'shipping_method' => 'Standard Shipping',
                'shipping_service' => $shippingRate['name'],
                'subtotal' => $subtotal,
                'shipping_total' => $shippingTotal,
                'tax_total' => $tax['amount'],
                'tax_breakdown' => $tax,
                'total' => round($subtotal + $shippingTotal + $tax['amount'], 2),
                'customer_notes' => $data['notes'] ?? null,
                'placed_at' => now(),
                'quoted_at' => $payingOnline ? now() : null,
            ]);

            foreach ($items as $item) {
                $variant = $item->variant;
                $unitPrice = $this->accountPrice((float) $variant->wholesale_price, $discount);
                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'sku' => $variant->product->sku,
                    'option' => $variant->optionLabel(),
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => round($unitPrice * $item->quantity, 2),
                ]);
            }

            $cart->items()->delete();

            return $order;
        });

        try {
            $order->user->notify(new OrderSubmitted($order));
            $adminEmail = config('commerce.order_notification_email');
            if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                Notification::route('mail', $adminEmail)->notify(new NewOrderForAdmin($order));
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        if ($data['payment_option'] === 'paypal') {
            try {
                return Inertia::location($paypal->startCheckout($order));
            } catch (Throwable $exception) {
                report($exception);

                return redirect()->route('orders.show', $order)
                    ->withErrors(['payment' => 'Your order was created, but PayPal is temporarily unavailable. Please try payment again from this order.']);
            }
        }

        return redirect()->route('orders.show', $order)->with('status', 'Order enquiry submitted.');
    }

    private function accountPrice(float $price, float $discount): float
    {
        return round($price * (1 - $discount / 100), 2);
    }

    /**
     * Flat 13% HST for Canadian orders, 0 otherwise. Mirrors the retail
     * checkout flat HST rate without province whitelist validation, since
     * the wholesale address form uses free-text province input.
     *
     * @return array{enabled: bool, label: string, jurisdiction: string, rate: float, amount: float, registration_number: ?string}
     */
    private function calculateWholesaleTax(float $taxableAmount, string $countryCode, string $province): array
    {
        $registered = (bool) config('retail-tax.gst_hst_registered');
        $isCanada = strtoupper(trim($countryCode)) === 'CA';
        $rate = ($registered && $isCanada) ? 0.13 : 0.0;

        return [
            'enabled' => $registered && $isCanada,
            'label' => 'HST',
            'jurisdiction' => $isCanada ? strtoupper(trim($province)) : strtoupper(trim($countryCode)),
            'rate' => $rate,
            'amount' => round(max(0, $taxableAmount) * $rate, 2),
            'registration_number' => config('retail-tax.registration_number'),
        ];
    }

    /** @return array<string, array<int, string>> */
    private function destinationRules(): array
    {
        return [
            'country' => ['required', 'string', 'max:100'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'postal_code' => ['required', 'string', 'max:20'],
        ];
    }

    private function cartItems(Request $request): Collection
    {
        $cart = Cart::query()->where('user_id', $request->user()->id)->first();
        if (! $cart) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        $items = $cart->items()->with('variant.product')->get();
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        return $items;
    }

    private function cartSubtotal(Request $request): float
    {
        $user = $request->user()->loadMissing('priceTier');
        $discount = (float) ($user->priceTier?->discount_percentage ?? 0);

        return round($this->cartItems($request)->sum(
            fn ($item) => $this->accountPrice((float) $item->variant->wholesale_price, $discount) * $item->quantity
        ), 2);
    }

    private function orderNumber(): string
    {
        do {
            $number = 'IGI-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
