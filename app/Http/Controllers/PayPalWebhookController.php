<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Notifications\OrderStatusChanged;
use App\Services\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PayPalWebhookController extends Controller
{
    public function __invoke(Request $request, PayPalService $paypal): JsonResponse
    {
        $eventId = $request->string('id')->toString();
        $eventType = $request->string('event_type')->toString();
        if ($eventId === '' || $eventType === '') {
            return response()->json(['message' => 'Invalid event'], 400);
        }

        $existing = WebhookEvent::where('provider', 'paypal')->where('provider_event_id', $eventId)->first();
        if ($existing?->processed_at) {
            return response()->json(['received' => true]);
        }

        try {
            if (! $paypal->verifyWebhook($request)) {
                return response()->json(['message' => 'Invalid signature'], 400);
            }
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Verification unavailable'], 503);
        }

        $event = WebhookEvent::firstOrCreate([
            'provider' => 'paypal',
            'provider_event_id' => $eventId,
        ], [
            'event_type' => $eventType,
            'payload_hash' => hash('sha256', $request->getContent()),
        ]);

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $providerOrderId = $request->input('resource.supplementary_data.related_ids.order_id');
            $payment = Payment::where('provider_order_id', $providerOrderId)->with('order')->first();
            if (! $payment) {
                return response()->json(['message' => 'Payment not available yet'], 409);
            }

            $amount = (string) $request->input('resource.amount.value');
            $currency = (string) $request->input('resource.amount.currency_code');
            if ($currency !== $payment->currency || round((float) $amount * 100) !== round((float) $payment->amount * 100)) {
                return response()->json(['message' => 'Amount mismatch'], 422);
            }

            DB::transaction(function () use ($payment, $request): void {
                $locked = Payment::lockForUpdate()->findOrFail($payment->id);
                if ($locked->status === 'completed') {
                    return;
                }
                $captureId = $request->string('resource.id')->toString();
                $locked->update([
                    'provider_capture_id' => $captureId,
                    'status' => 'completed',
                    'payer_email' => $request->input('resource.payee.email_address'),
                    'provider_metadata' => ['capture_status' => 'COMPLETED', 'source' => 'webhook'],
                    'completed_at' => now(),
                ]);
                $locked->order()->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'paypal',
                    'payment_reference' => $captureId,
                    'paid_at' => now(),
                    'status' => 'processing',
                ]);
            });

            try {
                $payment->order->refresh()->user->notify(new OrderStatusChanged($payment->order));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $event->update(['processed_at' => now()]);

        return response()->json(['received' => true]);
    }
}
