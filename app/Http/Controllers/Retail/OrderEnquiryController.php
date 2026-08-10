<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\ContactEnquiry;
use App\Models\Order;
use App\Notifications\ContactEnquiryReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class OrderEnquiryController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Orders/Enquiry', [
            'orderNumber' => $request->query('order'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $order = Order::query()
            ->where('order_number', $data['order_number'])
            ->where('sales_channel', 'retail')
            ->first();

        if (! $order) {
            throw ValidationException::withMessages(['order_number' => 'We could not find an order with that number. Please check and try again.']);
        }

        $enquiry = ContactEnquiry::create([
            'sales_channel' => 'retail',
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => "Order enquiry: {$order->order_number}",
            'message' => $data['message'],
            'ip_hash' => $request->ip()
                ? hash_hmac('sha256', $request->ip(), (string) config('app.key'))
                : null,
        ]);

        $recipient = config('commerce.order_notification_email') ?: config('commerce.company.email');
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            try {
                Notification::route('mail', $recipient)->notify(new ContactEnquiryReceived($enquiry));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return back()->with('status', 'Thank you. Your enquiry about your order has been received and our team will reply shortly.');
    }
}