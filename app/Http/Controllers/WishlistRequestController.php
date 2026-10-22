<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\WishlistRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use App\Notifications\WishlistRequestReceived;

class WishlistRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'sales_channel' => ['required', 'in:retail,wholesale'],
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
        ]);
        $variant = ProductVariant::query()->findOrFail($data['variant_id']);
        abort_unless($variant->is_active && $variant->stock_quantity <= 0, 422);
        $wishlistRequest = WishlistRequest::create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'sales_channel' => $data['sales_channel'],
            'email' => $data['email'],
            'name' => $data['name'],
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);

        $adminEmail = config('commerce.order_notification_email');
        if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            Notification::route('mail', $adminEmail)->notify(new WishlistRequestReceived($wishlistRequest));
        }

        return back()->with('status', 'Your notification request was sent to our team.');
    }
}
