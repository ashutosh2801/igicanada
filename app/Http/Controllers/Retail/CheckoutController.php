<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\NewOrderForAdmin;
use App\Notifications\RetailOrderSubmitted;
use App\Services\PayPalService;
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

        $subtotal = $cart->items->sum(fn ($item): float => (float) $item->variant->retail_price * $item->quantity);

        return Inertia::render('Checkout/Create', [
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'paypalConfigured' => $paypal->configured(),
        ]);
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
        ]);

        $payingOnline = $paypal->configured();
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
                    'product_name' => $variant->product->retail_name ?: $variant->product->name,
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

    private function orderNumber(): string
    {
        do {
            $number = 'LW-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
