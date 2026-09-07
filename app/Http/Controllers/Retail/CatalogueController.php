<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CatalogueController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('q'));
        $category = trim((string) $request->string('category'));

        $products = $this->retailProducts()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            }))
            ->when($category !== '', fn ($query) => $query->whereHas('categories', fn ($query) => $query->where('slug', $category)))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString()
            ->through(fn (Product $product): array => $this->card($product));

        return Inertia::render('Catalogue/Index', [
            'products' => $products,
            'categories' => Category::query()
                ->visibleForChannel('retail')
                ->where('is_active', true)
                ->whereHas('products', fn ($query) => $query->whereIn('visibility', ['retail', 'both']))
                ->orderBy('name')
                ->get(['name', 'slug']),
            'filters' => ['q' => $search, 'category' => $category],
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active && in_array($product->visibility, ['retail', 'both'], true), 404);

        $product->load([
            'categories:id,name,slug',
            'primaryMedia:id,disk,path,alt_text',
            'images:id,product_id,media_asset_id,path,alt_text',
            'images.mediaAsset:id,disk,path,alt_text',
            'variants' => fn ($query) => $query
                ->where('is_active', true)
                ->where('is_available_retail', true)
                ->whereNotNull('retail_price')
                ->orderBy('id'),
        ]);
        abort_if($product->variants->isEmpty(), 404);

        $variantImageAssets = $this->variantImageAssets($product);

        return Inertia::render('Catalogue/Show', [
            'product' => [
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'description' => trim((string) $product->description),
                'seoDescription' => str(strip_tags((string) $product->description))->squish()->limit(160)->toString(),
                'categories' => $product->categories->map->only(['name', 'slug']),
                'images' => $this->images($product),
                'variants' => $product->variants->map(fn ($variant) => [
                    'id' => $variant->id,
                    'label' => $variant->optionLabel(),
                    'color' => $variant->color,
                    'colorCode' => $variant->color_code,
                    'sizes' => $variant->sizeLabels(),
                    'images' => collect($variant->image_ids ?? [])
                        ->filter()
                        ->map(fn (mixed $id) => $variantImageAssets[(int) $id] ?? null)
                        ->filter()
                        ->values()
                        ->map(fn (MediaAsset $asset): array => [
                            'src' => $asset->url(),
                            'alt' => $asset->alt_text ?: $asset->title ?: $product->name,
                        ])
                        ->all(),
                    'price' => number_format((float) $variant->retail_price, 2, '.', ''),
                    'compareAtPrice' => $variant->retail_compare_at_price !== null
                        ? number_format((float) $variant->retail_compare_at_price, 2, '.', '')
                        : null,
                    'stockQuantity' => max(0, (int) $variant->stock_quantity),
                    'inStock' => $variant->stock_quantity > 0,
                ]),
            ],
        ]);
    }

    private function variantImageAssets(Product $product): Collection
    {
        $ids = $product->variants
            ->pluck('image_ids')
            ->flatten()
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return MediaAsset::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    private function retailProducts()
    {
        return Product::query()
            ->select(['id', 'sku', 'name', 'slug', 'primary_image_path', 'primary_media_asset_id'])
            ->where('is_active', true)
            ->whereIn('visibility', ['retail', 'both'])
            ->whereHas('variants', fn ($query) => $query
                ->where('is_active', true)
                ->where('is_available_retail', true)
                ->whereNotNull('retail_price'))
            ->withSum(['variants as stock_quantity' => fn ($query) => $query
                ->where('is_active', true)
                ->where('is_available_retail', true)], 'stock_quantity')
            ->withMin(['variants as minimum_retail_price' => fn ($query) => $query
                ->where('is_active', true)
                ->where('is_available_retail', true)], 'retail_price')
            ->withMin(['variants as minimum_retail_compare_at_price' => fn ($query) => $query
                ->where('is_active', true)
                ->where('is_available_retail', true)], 'retail_compare_at_price')
            ->with('primaryMedia:id,disk,path');
    }

    private function card(Product $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'slug' => $product->slug,
            'image' => $product->primaryImageUrl(),
            'price' => $product->minimum_retail_price !== null
                ? number_format((float) $product->minimum_retail_price, 2, '.', '')
                : null,
            'compareAtPrice' => $product->minimum_retail_compare_at_price !== null
                ? number_format((float) $product->minimum_retail_compare_at_price, 2, '.', '')
                : null,
            'inStock' => (int) $product->stock_quantity > 0,
        ];
    }

    private function images(Product $product): array
    {
        $images = collect();
        if ($url = $product->primaryImageUrl()) {
            $images->push(['src' => $url, 'alt' => $product->primaryMedia?->alt_text ?: $product->name]);
        }
        foreach ($product->images as $image) {
            if ($url = $image->url()) {
                $images->push(['src' => $url, 'alt' => $image->alt_text ?: $product->name]);
            }
        }

        return $images->unique('src')->values()->all();
    }
}
