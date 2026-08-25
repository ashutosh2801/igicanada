<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\ContactEnquiry;
use App\Models\Order;
use App\Notifications\NewOrderForAdmin;
use App\Notifications\OrderEnquiryReceived;
use App\Notifications\OrderEnquirySubmitted;
use App\Notifications\RetailOrderSubmitted;
use App\Services\PayPalService;
use App\Services\RetailAddressService;
use App\Services\RetailCartService;
use App\Services\RetailTaxService;
use App\Services\StandardShippingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function create(Request $request, RetailCartService $retailCart, PayPalService $paypal): InertiaResponse|RedirectResponse
    {
        $cart = $retailCart->find($request)?->load('items.variant');
        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('retail.cart.index');
        }

        return Inertia::render('Checkout/Create', $this->checkoutProps($cart, $paypal, ['addresses' => app(RetailAddressService::class)->all($request)]));
    }

    public function quote(
        Request $request,
        RetailCartService $retailCart,
        StandardShippingService $shipping,
        RetailTaxService $taxes,
    ): Response {
        $data = $request->validate([
            'country' => ['required', 'string', 'in:Canada,United States'],
            'country_code' => ['required', 'string', 'in:CA,US'],
            'province' => ['required', 'string', 'max:100'],
        ]);

        $cart = $retailCart->find($request)?->load('items.variant');
        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'Your bag is empty.'], 422);
        }

        $subtotal = round((float) $cart->items->sum(fn ($item): float => (float) $item->variant->retail_price * $item->quantity), 2);

        $shippingName = null;
        $shippingTotal = null;
        try {
            $shippingRate = $shipping->quote($subtotal, $data['country'], $data['country_code'], 'retail');
            $shippingName = $shippingRate['name'];
            $shippingTotal = round((float) $shippingRate['price'], 2);
        } catch (InvalidArgumentException) {
            // shipping unavailable for this destination
        }

        $tax = $taxes->calculate($subtotal + ($shippingTotal ?? 0), $data['country_code'], $data['province']);

        return response()->json([
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'shippingTotal' => $shippingTotal !== null ? number_format($shippingTotal, 2, '.', '') : null,
            'shippingName' => $shippingName,
            'tax' => [
                'label' => $tax['label'],
                'amount' => number_format((float) $tax['amount'], 2, '.', ''),
                'rate' => $tax['rate'],
            ],
            'total' => number_format(round($subtotal + ($shippingTotal ?? 0) + (float) $tax['amount'], 2), 2, '.', ''),
        ]);
    }

    private function checkoutProps($cart, PayPalService $paypal, array $extra = []): array
    {
        $subtotal = round((float) $cart->items->sum(fn ($item): float => (float) $item->variant->retail_price * $item->quantity), 2);

        $items = $cart->items->map(function ($item): array {
            $variant = $item->variant;
            $unitPrice = (float) $variant->retail_price;
            $product = $variant->product;

            return [
                'name' => $product->name,
                'option' => $variant->optionLabel(),
                'image' => $product->primaryImageUrl(),
                'quantity' => $item->quantity,
                'unitPrice' => number_format($unitPrice, 2, '.', ''),
                'lineTotal' => number_format(round($unitPrice * $item->quantity, 2), 2, '.', ''),
            ];
        })->values();

        $paymentMethods = array_filter([
            [
                'id' => 'paypal',
                'label' => 'Pay online with PayPal',
                'description' => 'You will be redirected to PayPal to complete payment securely.',
                'available' => $paypal->configured(),
            ],
            [
                'id' => 'manual',
                'label' => 'Pay by bank transfer / invoice',
                'description' => 'We will email you payment instructions after receiving your order.',
                'available' => true,
            ],
        ], fn (array $method): bool => $method['available']);

        return array_merge([
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'paypalConfigured' => $paypal->configured(),
            'paymentMethods' => array_values($paymentMethods),
            'defaultPaymentMethod' => $paypal->configured() ? 'paypal' : 'manual',
            'items' => $items,
        ], $extra);
    }

    public function store(
        Request $request,
        RetailCartService $retailCart,
        StandardShippingService $shipping,
        RetailTaxService $taxes,
        PayPalService $paypal,
    ): Response {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:300'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'in:Canada,United States'],
            'country_code' => ['required', 'string', 'in:CA,US'],
            'postal_code' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'string', 'in:paypal,manual'],
        ]);

        if ($data['payment_method'] === 'paypal' && ! $paypal->configured()) {
            throw ValidationException::withMessages(['payment_method' => 'Online payment is currently unavailable. Please choose another option.']);
        }

        $payingOnline = $data['payment_method'] === 'paypal' && $paypal->configured();
        $order = DB::transaction(function () use ($request, $data, $retailCart, $shipping, $taxes, $payingOnline): Order {
            $cart = $retailCart->find($request);
            if (! $cart) {
                throw ValidationException::withMessages(['cart' => 'Your bag is empty.']);
            }

            $items = $cart->items()->with('variant.product')->lockForUpdate()->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Your bag is empty.']);
            }

            $subtotal = 0;
            foreach ($items as $item) {
                $variant = $item->variant;
                if (! $variant->is_active || ! $variant->is_available_retail || $variant->retail_price === null || $item->quantity > $variant->stock_quantity) {
                    throw ValidationException::withMessages(['cart' => "{$variant->product->name} is no longer available in the requested quantity."]);
                }
                $subtotal += (float) $variant->retail_price * $item->quantity;
            }
            $subtotal = round($subtotal, 2);

            try {
                $shippingRate = $shipping->quote($subtotal, $data['country'], $data['country_code'], 'retail');
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['country' => $exception->getMessage()]);
            }

            $shippingTotal = round((float) $shippingRate['price'], 2);
            $tax = $taxes->calculate($subtotal + $shippingTotal, $data['country_code'], $data['province']);
            $order = Order::create([
                'order_number' => $this->orderNumber(),
                'sales_channel' => 'retail',
                'user_id' => null,
                'customer_email' => $data['email'],
                'shipping_address' => [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'province' => $data['province'],
                    'country' => $data['country'],
                    'postal_code' => $data['postal_code'],
                ],
                'currency' => 'CAD',
                'status' => $payingOnline ? 'quoted' : 'awaiting_quote',
                'payment_status' => 'unpaid',
                'payment_method' => $payingOnline ? 'paypal' : 'manual',
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
                $unitPrice = (float) $variant->retail_price;
                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'sku' => $variant->sku ?: $variant->product->sku,
                    'option' => $variant->optionLabel(),
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => round($unitPrice * $item->quantity, 2),
                ]);
                $variant->decrement('stock_quantity', $item->quantity);
            }

            $cart->items()->delete();

            return $order;
        });

        $orderIds = collect($request->session()->get('retail_order_ids', []))->push($order->id)->unique()->values()->all();
        $request->session()->put('retail_order_ids', $orderIds);

        try {
            Notification::route('mail', $order->customer_email)->notify(new RetailOrderSubmitted($order));
            $adminEmail = config('commerce.order_notification_email');
            if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                Notification::route('mail', $adminEmail)->notify(new NewOrderForAdmin($order));
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        if ($payingOnline) {
            try {
                return Inertia::location($paypal->startCheckout($order));
            } catch (Throwable $exception) {
                report($exception);

                return redirect()->route('retail.orders.show', $order)
                    ->withErrors(['payment' => 'Your order was saved, but PayPal is temporarily unavailable. You can retry payment.']);
            }
        }

        return redirect()->route('retail.orders.show', $order)->with('status', 'Your order has been received.');
    }

    public function enquiry(Request $request, RetailCartService $retailCart): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:300'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'in:Canada,United States'],
            'country_code' => ['required', 'string', 'in:CA,US'],
            'postal_code' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $cart = $retailCart->find($request)?->load('items.variant.product');
        if (! $cart || $cart->items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Your bag is empty.']);
        }

        $items = $cart->items->map(function ($item): array {
            $variant = $item->variant;
            $unitPrice = (float) $variant->retail_price;

            return [
                'name' => $variant->product->name,
                'option' => $variant->optionLabel(),
                'quantity' => $item->quantity,
                'unit_price' => number_format($unitPrice, 2, '.', ''),
                'line_total' => number_format(round($unitPrice * $item->quantity, 2), 2, '.', ''),
            ];
        })->all();
        $subtotal = number_format(round((float) $cart->items->sum(fn ($item): float => (float) $item->variant->retail_price * $item->quantity), 2), 2, '.', '');

        $message = "Shipping to: {$data['name']}, {$data['address']}, {$data['city']}, {$data['province']} {$data['postal_code']}, {$data['country']}. Phone: {$data['phone']}.";
        if (! empty($data['notes'])) {
            $message .= " Notes: {$data['notes']}";
        }

        $enquiry = ContactEnquiry::create([
            'sales_channel' => 'retail',
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'subject' => 'Checkout order enquiry',
            'message' => $message,
            'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), (string) config('app.key')) : null,
        ]);

        $adminRecipient = config('commerce.order_notification_email') ?: config('commerce.company.email');
        if (filter_var($adminRecipient, FILTER_VALIDATE_EMAIL)) {
            try {
                Notification::route('mail', $adminRecipient)->notify(new OrderEnquiryReceived($enquiry, [
                    'items' => $items,
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                ]));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $summary = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'city' => $data['city'],
            'province' => $data['province'],
            'postal_code' => $data['postal_code'],
            'country' => $data['country'],
            'notes' => $data['notes'] ?? null,
            'items' => $items,
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ];

        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            try {
                Notification::route('mail', $data['email'])->notify(new OrderEnquirySubmitted($summary));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $request->session()->flash('enquiry_summary', $summary);

        return redirect()->route('retail.checkout.enquiry.received');
    }

    public function enquiryReceived(Request $request): InertiaResponse
    {
        $summary = $request->session()->get('enquiry_summary');
        abort_unless(is_array($summary), 404);

        return Inertia::render('Orders/EnquiryReceived', ['summary' => $summary]);
    }

    private function orderNumber(): string
    {
        do {
            $number = 'LW-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
