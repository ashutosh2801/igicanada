<?php

namespace App\Http\Controllers;

use App\Models\ContactEnquiry;
use App\Notifications\ContactEnquiryReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ContactController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Contact/Create', [
            'company' => config('commerce.company'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'website' => ['nullable', 'prohibited'],
        ]);
        unset($data['website']);

        $data['sales_channel'] = $request->attributes->get('sales_channel', 'wholesale');
        $data['ip_hash'] = $request->ip()
            ? hash_hmac('sha256', $request->ip(), (string) config('app.key'))
            : null;
        $enquiry = ContactEnquiry::create($data);

        $recipient = config('commerce.order_notification_email') ?: config('commerce.company.email');
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            try {
                Notification::route('mail', $recipient)->notify(new ContactEnquiryReceived($enquiry));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return back()->with('status', 'Thank you. Your enquiry has been received and our team will reply shortly.');
    }
}
