<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RetailCartService
{
    public function find(Request $request): ?Cart
    {
        return Cart::query()
            ->where('sales_channel', 'retail')
            ->where('session_key', $this->sessionKey($request))
            ->first();
    }

    public function get(Request $request): Cart
    {
        return Cart::firstOrCreate([
            'sales_channel' => 'retail',
            'session_key' => $this->sessionKey($request),
        ]);
    }

    public function owns(Request $request, CartItem $item): bool
    {
        return $item->cart->sales_channel === 'retail'
            && hash_equals((string) $item->cart->session_key, $this->sessionKey($request));
    }

    public function count(Request $request): int
    {
        return (int) ($this->find($request)?->items()->sum('quantity') ?? 0);
    }

    /** @return array{count: int, items: array<int, array<string, mixed>>, subtotal: string} */
    public function summary(Request $request): array
    {
        $cart = $this->find($request)?->load('items.variant.product.primaryMedia');

        if (! $cart) {
            return ['count' => 0, 'items' => [], 'subtotal' => '0.00'];
        }

        $imageIds = $cart->items
            ->pluck('variant.image_ids')
            ->flatten()
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
        $images = MediaAsset::query()->whereIn('id', $imageIds)->get()->keyBy('id');
        $subtotal = 0;

        $items = $cart->items->map(function (CartItem $item) use ($images, &$subtotal): array {
            $unitPrice = (float) $item->variant->retail_price;
            $lineTotal = round($unitPrice * $item->quantity, 2);
            $subtotal += $lineTotal;
            $image = collect($item->variant->image_ids ?? [])
                ->map(fn (mixed $id) => $images->get((int) $id))
                ->filter()
                ->first()?->url();

            return [
                'id' => $item->id,
                'product' => $item->variant->product->name,
                'slug' => $item->variant->product->slug,
                'option' => $item->variant->optionLabel(),
                'image' => $image ?? $item->variant->product->primaryImageUrl(),
                'quantity' => $item->quantity,
                'unitPrice' => number_format($unitPrice, 2, '.', ''),
                'lineTotal' => number_format($lineTotal, 2, '.', ''),
            ];
        })->values()->all();

        return [
            'count' => (int) $cart->items->sum('quantity'),
            'items' => $items,
            'subtotal' => number_format($subtotal, 2, '.', ''),
        ];
    }

    private function sessionKey(Request $request): string
    {
        $token = $request->session()->get('retail_cart_token');
        if (! is_string($token) || strlen($token) < 32) {
            $token = Str::random(64);
            $request->session()->put('retail_cart_token', $token);
        }

        return hash_hmac('sha256', $token, (string) config('app.key'));
    }
}
