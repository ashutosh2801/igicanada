<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Notifications\OrderStatusChanged;
use App\Notifications\RetailPaymentConfirmed;
use App\Services\PayPalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

class PayPalController extends Controller
{
    public function create(Request $request, Order $order, PayPalService $paypal): RedirectResponse
    {
        $this->authorizeOrder($request, $order);
        if (! $paypal->configured()) {
            throw ValidationException::withMessages(['payment' => 'PayPal is not configured yet. Please contact IGI Canada.']);
        }
        if ($order->status !== 'quoted' || $order->payment_status !== 'unpaid' || (float) $order->total <= 0) {
            throw ValidationException::withMessages(['payment' => 'This order is not ready for online payment.']);
        }

        try {
            return redirect()->away($paypal->startCheckout($order));
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages(['payment' => 'PayPal is temporarily unavailable. Please try again.']);
        }
    }

    public function capture(Request $request, PayPalService $paypal): RedirectResponse
    {
        $providerOrderId = trim((string) $request->query('token'));
        $payment = Payment::where('provider_order_id', $providerOrderId)->with('order')->firstOrFail();
        $this->authorizeOrder($request, $payment->order);
        if ($payment->order->status === 'cancelled' || $payment->order->inventory_released_at) {
            throw ValidationException::withMessages(['payment' => 'This order has been cancelled and can no longer be paid.']);
        }

        if ($payment->status === 'completed') {
            return redirect()->to($this->orderUrl($payment->order))->with('status', 'Payment already completed.');
        }

        try {
            $result = $paypal->captureOrder($providerOrderId, $payment->id);
            $capture = data_get($result, 'purchase_units.0.payments.captures.0');
            $capturedAmount = data_get($capture, 'amount.value');
            $capturedCurrency = data_get($capture, 'amount.currency_code');
            if (($result['status'] ?? null) !== 'COMPLETED' || data_get($capture, 'status') !== 'COMPLETED') {
                throw ValidationException::withMessages(['payment' => 'PayPal has not completed this payment.']);
            }
            if ((string) $capturedCurrency !== $payment->currency || round((float) $capturedAmount * 100) !== round((float) $payment->amount * 100)) {
                throw ValidationException::withMessages(['payment' => 'Captured PayPal amount does not match the invoice.']);
            }

            DB::transaction(function () use ($payment, $capture, $result): void {
                $locked = Payment::lockForUpdate()->findOrFail($payment->id);
                if ($locked->status === 'completed') {
                    return;
                }
                $locked->update([
                    'provider_capture_id' => data_get($capture, 'id'),
                    'status' => 'completed',
                    'payer_email' => data_get($result, 'payer.email_address'),
                    'provider_metadata' => ['capture_status' => data_get($capture, 'status')],
                    'completed_at' => now(),
                ]);
                $locked->order()->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'paypal',
                    'payment_reference' => data_get($capture, 'id'),
                    'paid_at' => now(),
                    'status' => 'processing',
                ]);
            });

            try {
                $order = $payment->order->refresh();
                if ($order->user) {
                    $order->user->notify(new OrderStatusChanged($order));
                } elseif (filter_var($order->customer_email, FILTER_VALIDATE_EMAIL)) {
                    Notification::route('mail', $order->customer_email)->notify(new RetailPaymentConfirmed($order));
                }
            } catch (Throwable $exception) {
                report($exception);
            }

            return redirect()->to($this->orderUrl($payment->order))->with('status', 'PayPal payment completed.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            $payment->update(['status' => 'failed']);

            throw ValidationException::withMessages(['payment' => 'PayPal could not complete the payment. Please try again.']);
        }
    }

    public function cancel(Request $request): RedirectResponse
    {
        $payment = Payment::where('provider_order_id', $request->query('token'))->with('order')->firstOrFail();
        $this->authorizeOrder($request, $payment->order);
        if ($payment->status === 'created') {
            $payment->update(['status' => 'cancelled']);
        }

        return redirect()->to($this->orderUrl($payment->order))->with('status', 'PayPal payment was cancelled. You can retry when ready.');
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        if ($order->sales_channel === 'retail') {
            abort_unless(in_array($order->id, $request->session()->get('retail_order_ids', []), true), 404);

            return;
        }

        abort_unless($request->user() && $order->user_id === $request->user()->id, 404);
    }

    private function orderUrl(Order $order): string
    {
        return $order->sales_channel === 'retail'
            ? route('retail.orders.show', $order)
            : route('orders.show', $order);
    }
}
