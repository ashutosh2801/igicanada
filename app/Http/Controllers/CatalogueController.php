<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CatalogueController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $search = trim((string) $request->string('q'));
        $category = trim((string) $request->string('category'));
        $user = $request->user()?->loadMissing('priceTier');
        $canViewPricing = $user?->isApprovedWholesale() ?? false;
        $discount = $canViewPricing ? (float) ($user->priceTier?->discount_percentage ?? 0) : 0;

        $products = Product::query()
            ->select(['id', 'sku', 'name', 'slug', 'primary_image_path', 'primary_media_asset_id'])
            ->where('is_active', true)
            ->whereIn('visibility', ['wholesale', 'both'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            }))
            ->when($category !== '', fn ($query) => $query->whereHas('categories', fn ($query) => $query->where('slug', $category)))
            ->withCount('variants')
            ->withSum(['variants as stock_quantity' => fn ($query) => $query->where('is_active', true)], 'stock_quantity')
            ->withMin(['variants as minimum_wholesale_price' => fn ($query) => $query->where('is_active', true)], 'wholesale_price')
            ->with('primaryMedia:id,disk,path')
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString()
            ->through(fn (Product $product) => $this->productCard($product, $canViewPricing, $discount));

        return Inertia::render('Catalogue/Index', [
            'products' => $products,
            'categories' => Category::query()
                ->visibleForChannel('wholesale')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['name', 'slug']),
            'filters' => ['q' => $search, 'category' => $category],
            'pricing' => [
                'authorized' => $canViewPricing,
                'tier' => $canViewPricing ? $user->priceTier?->name : null,
            ],
        ]);
    }

    public function show(Request $request, Product $product): Response
    {
        abort_unless($product->is_active && in_array($product->visibility, ['wholesale', 'both'], true), 404);

        $product->load([
            'categories:id,name,slug',
            'primaryMedia:id,disk,path,alt_text',
            'images:id,product_id,media_asset_id,path,alt_text',
            'images.mediaAsset:id,disk,path,alt_text',
            'variants' => fn ($query) => $query->where('is_active', true)->orderBy('id'),
        ]);

        $user = $request->user()?->loadMissing('priceTier');
        $canViewPricing = $user?->isApprovedWholesale() ?? false;
        $discount = $canViewPricing ? (float) ($user->priceTier?->discount_percentage ?? 0) : 0;

        $variantImageAssets = $this->variantImageAssets($product);

        return Inertia::render('Catalogue/Show', [
            'product' => [
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'description' => trim((string) $product->description),
                'seoDescription' => str(strip_tags((string) $product->description))->squish()->limit(160)->toString(),
                'categories' => $product->categories->map->only(['name', 'slug']),
                'images' => $this->productImages($product),
                'variants' => $product->variants->map(function ($variant) use ($canViewPricing, $discount, $product, $variantImageAssets) {
                    $basePrice = (float) $variant->wholesale_price;

                    return [
                        'id' => $variant->id,
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
                        'inStock' => $variant->stock_quantity > 0,
                        'stockQuantity' => $canViewPricing ? $variant->stock_quantity : null,
                        'minimumQuantity' => $variant->wholesale_minimum_quantity,
                        'wholesalePrice' => $canViewPricing ? number_format($basePrice, 2, '.', '') : null,
                        'accountPrice' => $canViewPricing ? number_format($basePrice * (1 - $discount / 100), 2, '.', '') : null,
                    ];
                }),
            ],
            'pricing' => [
                'authorized' => $canViewPricing,
                'tier' => $canViewPricing ? $user->priceTier?->name : null,
                'discountPercentage' => $canViewPricing ? number_format($discount, 2, '.', '') : null,
            ],
            'similarProducts' => $this->similarProducts($product, $canViewPricing, $discount),
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

    /** @return Collection<int, array<string, mixed>> */
    private function similarProducts(Product $product, bool $canViewPricing, float $discount): Collection
    {
        $categoryIds = $product->categories->pluck('id');
        $similar = $this->productCardQuery()
            ->whereKeyNot($product->id)
            ->when($categoryIds->isNotEmpty(), fn (Builder $query) => $query->whereHas(
                'categories',
                fn (Builder $query) => $query->whereIn('categories.id', $categoryIds),
            ))
            ->orderBy('name')
            ->limit(4)
            ->get();

        if ($similar->count() < 4) {
            $fallback = $this->productCardQuery()
                ->whereKeyNot($product->id)
                ->whereNotIn('id', $similar->pluck('id'))
                ->orderBy('name')
                ->limit(4 - $similar->count())
                ->get();
            $similar = $similar->concat($fallback);
        }

        return $similar->map(fn (Product $item) => $this->productCard($item, $canViewPricing, $discount))->values();
    }

    private function productCardQuery(): Builder
    {
        return Product::query()
            ->select(['id', 'sku', 'name', 'slug', 'primary_image_path', 'primary_media_asset_id'])
            ->where('is_active', true)
            ->whereIn('visibility', ['wholesale', 'both'])
            ->withCount('variants')
            ->withSum(['variants as stock_quantity' => fn ($query) => $query->where('is_active', true)], 'stock_quantity')
            ->withMin(['variants as minimum_wholesale_price' => fn ($query) => $query->where('is_active', true)], 'wholesale_price')
            ->with('primaryMedia:id,disk,path');
    }

    /** @return array<string, mixed> */
    private function productCard(Product $product, bool $canViewPricing, float $discount): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'slug' => $product->slug,
            'image' => $product->primaryImageUrl(),
            'variants' => $product->variants_count,
            'inStock' => (int) $product->stock_quantity > 0,
            'wholesalePrice' => $canViewPricing && $product->minimum_wholesale_price !== null
                ? number_format((float) $product->minimum_wholesale_price, 2, '.', '')
                : null,
            'accountPrice' => $canViewPricing && $product->minimum_wholesale_price !== null
                ? number_format((float) $product->minimum_wholesale_price * (1 - $discount / 100), 2, '.', '')
                : null,
        ];
    }

    /** @return Collection<int, array{src: string, alt: string}> */
    private function productImages(Product $product): Collection
    {
        $images = collect();

        if ($primaryUrl = $product->primaryImageUrl()) {
            $images->push([
                'src' => $primaryUrl,
                'alt' => $product->primaryMedia?->alt_text ?: $product->name,
            ]);
        }

        foreach ($product->images as $image) {
            if ($url = $image->url()) {
                $images->push([
                    'src' => $url,
                    'alt' => $image->alt_text ?: $image->mediaAsset?->alt_text ?: $product->name,
                ]);
            }
        }

        return $images->unique('src')->values();
    }
}
