<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
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
