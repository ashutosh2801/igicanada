<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\WishlistRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishlistRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'sales_channel' => ['required', 'in:retail,wholesale'],
            'email' => ['nullable', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);
        $variant = ProductVariant::query()->findOrFail($data['variant_id']);
        abort_unless($variant->is_active && $variant->stock_quantity <= 0, 422);
        WishlistRequest::create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'sales_channel' => $data['sales_channel'],
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? null,
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);
        return back()->with('status', 'Added to your wishlist. We will let you know when it is available.');
    }
}
