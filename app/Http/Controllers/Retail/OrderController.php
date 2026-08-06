<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function show(Request $request, Order $order, PayPalService $paypal): Response
    {
        abort_unless(
            $order->sales_channel === 'retail'
            && in_array($order->id, $request->session()->get('retail_order_ids', []), true),
            404,
        );
        $order->load('items');

        return Inertia::render('Orders/Show', [
            'order' => [
                'id' => $order->id,
                'number' => $order->order_number,
                'status' => $order->status,
                'paymentStatus' => $order->payment_status,
                'subtotal' => $order->subtotal,
                'shippingTotal' => $order->shipping_total,
                'shippingService' => $order->shipping_service,
                'taxTotal' => $order->tax_total,
                'taxLabel' => data_get($order->tax_breakdown, 'label', 'Tax'),
                'total' => $order->total,
                'currency' => $order->currency,
                'address' => $order->shipping_address,
                'items' => $order->items->map->only(['product_name', 'option', 'quantity', 'unit_price', 'line_total']),
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
