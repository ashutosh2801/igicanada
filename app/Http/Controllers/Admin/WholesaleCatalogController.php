<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class WholesaleCatalogController extends Controller
{
    public function download(Request $request, string $token): Response
    {
        abort_unless($request->user('admin'), 403);

        $data = Cache::pull('wholesale-catalog-download:'.$token);
        abort_unless(is_array($data), 404);

        $query = Product::query()
            ->where('is_active', true)
            ->whereIn('visibility', ['wholesale', 'both'])
            ->whereIn('id', $data['product_ids'] ?? [])
            ->with([
                'primaryMedia:id,disk,path,alt_text',
                'images:id,product_id,media_asset_id,path,alt_text,position',
                'images.mediaAsset:id,disk,path,alt_text,title',
                'variants' => fn ($query) => $query->where('is_active', true)->where('is_available_wholesale', true)->orderBy('id'),
            ])
            ->orderBy('name');

        if (filled($data['category_id'] ?? null)) {
            $query->whereHas('categories', fn ($categories) => $categories->whereKey($data['category_id']));
        }

        $products = $query->get()->map(function (Product $product): array {
            $product->name = $this->safeUtf8($product->name);
            $product->sku = $this->safeUtf8($product->sku);
            $product->description = $this->safeUtf8($product->description);

            foreach ($product->variants as $variant) {
                $variant->color = $this->safeUtf8($variant->color);
                $variant->sizes = collect($variant->sizes ?? [])
                    ->map(fn ($size): string => $this->safeUtf8((string) $size))
                    ->all();
            }

            $images = collect([$product->primaryImageUrl(), ...$product->images->map->url()->all()])
                ->filter()
                ->unique()
                ->take(2)
                ->values();

            return ['product' => $product, 'images' => $images];
        });

        abort_if($products->isEmpty(), 422, 'Select at least one wholesale product.');

        $date = Carbon::parse($data['catalog_date']);

        return Pdf::setOption(['isRemoteEnabled' => true])
            ->loadView('pdf.wholesale-catalog', [
                'products' => $products,
                'catalogDate' => $date,
                'showPrices' => (bool) ($data['show_prices'] ?? true),
                'showStock' => (bool) ($data['show_stock'] ?? false),
                'company' => config('commerce.company'),
            ])
            ->setPaper('letter')
            ->download('IGI-Canada-Wholesale-Catalog-'.$date->format('Y-m-d').'.pdf');
    }

    private function safeUtf8(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

        return is_string($clean) ? $clean : '';
    }
}
