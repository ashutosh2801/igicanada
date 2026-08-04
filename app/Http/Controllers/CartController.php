<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user()->loadMissing('priceTier');
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $cart->load('items.variant.product.primaryMedia');
        $discount = (float) ($user->priceTier?->discount_percentage ?? 0);
        $subtotal = 0;

        $items = $cart->items->map(function (CartItem $item) use ($discount, &$subtotal) {
            $unitPrice = $this->accountPrice((float) $item->variant->wholesale_price, $discount);
            $lineTotal = $unitPrice * $item->quantity;
            $subtotal += $lineTotal;

            return [
                'id' => $item->id,
                'product' => $item->variant->product->name,
                'slug' => $item->variant->product->slug,
                'sku' => $item->variant->product->sku,
                'option' => $item->variant->optionLabel(),
                'image' => $item->variant->product->primaryImageUrl(),
                'quantity' => $item->quantity,
                'minimumQuantity' => $item->variant->wholesale_minimum_quantity,
                'maximumQuantity' => max(0, $item->variant->stock_quantity),
                'unitPrice' => number_format($unitPrice, 2, '.', ''),
                'lineTotal' => number_format($lineTotal, 2, '.', ''),
            ];
        });

        return Inertia::render('Cart/Index', [
            'items' => $items,
            'subtotal' => number_format($subtotal, 2, '.', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        $variant = ProductVariant::query()->where('is_active', true)->findOrFail($data['variant_id']);
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $item = $cart->items()->firstOrNew(['product_variant_id' => $variant->id]);
        $quantity = ($item->exists ? $item->quantity : 0) + $data['quantity'];
        $this->validateQuantity($variant, $quantity);
        $item->quantity = $quantity;
        $item->save();

        return back()->with('status', 'Product added to cart.');
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->cart->user_id === $request->user()->id, 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);
        $variant = $item->variant;
        $this->validateQuantity($variant, $data['quantity']);
        $item->update(['quantity' => $data['quantity']]);

        return back();
    }

    public function destroy(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->cart->user_id === $request->user()->id, 404);
        $item->delete();

        return back();
    }

    private function validateQuantity(ProductVariant $variant, int $quantity): void
    {
        if ($quantity < $variant->wholesale_minimum_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Minimum order quantity is {$variant->wholesale_minimum_quantity}.",
            ]);
        }

        if ($quantity > $variant->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$variant->stock_quantity} units are currently available.",
            ]);
        }
    }

    private function accountPrice(float $wholesalePrice, float $discount): float
    {
        return round($wholesalePrice * (1 - $discount / 100), 2);
    }
}
