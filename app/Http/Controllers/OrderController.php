<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Orders/Index', [
            'orders' => $request->user()->orders()->latest('placed_at')->get()->map(fn (Order $order) => [
                'id' => $order->id,
                'number' => $order->order_number,
                'status' => $order->status,
                'paymentStatus' => $order->payment_status,
                'total' => $order->total,
                'currency' => $order->currency,
                'placedAt' => $order->placed_at?->toFormattedDateString(),
            ]),
        ]);
    }

    public function show(Request $request, Order $order, PayPalService $paypal): Response
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $order->load('items');

        return Inertia::render('Orders/Show', [
            'order' => [
                'id' => $order->id,
                'number' => $order->order_number,
                'status' => $order->status,
                'paymentStatus' => $order->payment_status,
                'currency' => $order->currency,
                'subtotal' => $order->subtotal,
                'shippingTotal' => $order->shipping_total,
                'shippingMethod' => $order->shipping_method,
                'shippingService' => $order->shipping_service,
                'taxTotal' => $order->tax_total,
                'total' => $order->total,
                'address' => $order->shipping_address,
                'notes' => $order->customer_notes,
                'placedAt' => $order->placed_at?->toFormattedDateString(),
                'items' => $order->items->map->only(['product_name', 'sku', 'option', 'quantity', 'unit_price', 'line_total']),
            ],
            'payment' => [
                'paypalEnabled' => $paypal->configured(),
                'canPay' => $paypal->configured()
                    && $order->status === 'quoted'
                    && $order->payment_status === 'unpaid'
                    && (float) $order->total > 0,
            ],
        ]);
    }
}
