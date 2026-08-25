<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\MediaAsset;
use App\Models\ProductVariant;
use App\Services\RetailCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(Request $request, RetailCartService $retailCart): Response
    {
        $cart = $retailCart->get($request);
        $cart->load('items.variant.product.primaryMedia');
        $subtotal = 0;
        $variantImages = $this->variantImages($cart->items->pluck('variant'));

        return Inertia::render('Cart/Index', [
            'items' => $cart->items->map(function (CartItem $item) use (&$subtotal, $variantImages): array {
                $unitPrice = (float) $item->variant->retail_price;
                $lineTotal = round($unitPrice * $item->quantity, 2);
                $subtotal += $lineTotal;

                return [
                    'id' => $item->id,
                    'product' => $item->variant->product->name,
                    'slug' => $item->variant->product->slug,
                    'color' => $item->variant->color,
                    'colorCode' => $item->variant->color_code,
                    'sizes' => $item->variant->sizeLabels(),
                    'option' => $item->variant->optionLabel(),
                    'image' => $variantImages[$item->variant->id] ?? $item->variant->product->primaryImageUrl(),
                    'quantity' => $item->quantity,
                    'maximumQuantity' => max(0, (int) $item->variant->stock_quantity),
                    'unitPrice' => number_format($unitPrice, 2, '.', ''),
                    'lineTotal' => number_format($lineTotal, 2, '.', ''),
                ];
            }),
            'subtotal' => number_format($subtotal, 2, '.', ''),
        ]);
    }

    private function variantImages(Collection $variants): array
    {
        $ids = $variants
            ->pluck('image_ids')
            ->flatten()
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $assets = MediaAsset::query()->whereIn('id', $ids)->get()->keyBy('id');
        $map = [];

        foreach ($variants as $variant) {
            foreach ($variant->image_ids ?? [] as $id) {
                if (isset($assets[(int) $id])) {
                    $map[$variant->id] = $assets[(int) $id]->url();
                    break;
                }
            }
        }

        return $map;
    }

    public function store(Request $request, RetailCartService $retailCart): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        $variant = ProductVariant::query()
            ->where('is_active', true)
            ->where('is_available_retail', true)
            ->whereNotNull('retail_price')
            ->whereHas('product', fn ($query) => $query->where('is_active', true)->whereIn('visibility', ['retail', 'both']))
            ->findOrFail($data['variant_id']);

        $cart = $retailCart->get($request);
        $item = $cart->items()->firstOrNew(['product_variant_id' => $variant->id]);
        $quantity = ($item->exists ? $item->quantity : 0) + $data['quantity'];
        $this->validateQuantity($variant, $quantity);
        $item->quantity = $quantity;
        $item->save();

        return back()->with('status', 'Added to your bag.');
    }

    public function update(Request $request, CartItem $item, RetailCartService $retailCart): RedirectResponse
    {
        abort_unless($retailCart->owns($request, $item), 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);
        $this->validateQuantity($item->variant, $data['quantity']);
        $item->update(['quantity' => $data['quantity']]);

        return back();
    }

    public function destroy(Request $request, CartItem $item, RetailCartService $retailCart): RedirectResponse
    {
        abort_unless($retailCart->owns($request, $item), 404);
        $item->delete();

        return back();
    }

    private function validateQuantity(ProductVariant $variant, int $quantity): void
    {
        if (! $variant->is_active || ! $variant->is_available_retail || $variant->retail_price === null) {
            throw ValidationException::withMessages(['quantity' => 'This option is no longer available.']);
        }
        if ($quantity > $variant->stock_quantity) {
            throw ValidationException::withMessages(['quantity' => "Only {$variant->stock_quantity} units are currently available."]);
        }
    }
}
