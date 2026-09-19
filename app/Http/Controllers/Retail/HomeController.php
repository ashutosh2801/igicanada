<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomepageSetting;
use App\Models\Product;
use App\Support\StorefrontAsset;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $settings = HomepageSetting::query()->forChannel('retail')->firstOrFail();
        $heroImages = collect($settings->hero_image_paths)
            ->map(fn ($path): ?string => StorefrontAsset::uploaded($path))
            ->filter()
            ->values();

        if ($heroImages->isEmpty() && $settings->hero_image_path) {
            $heroImages->push(StorefrontAsset::uploaded($settings->hero_image_path));
        }
        $products = Product::query()
            ->where('is_active', true)
            ->whereIn('visibility', ['retail', 'both'])
            ->whereHas('variants', fn ($query) => $query
                ->where('is_active', true)
                ->where('is_available_retail', true))
            ->with('primaryMedia')
            ->withMin(['variants as minimum_retail_price' => fn ($query) => $query
                ->where('is_active', true)
                ->where('is_available_retail', true)], 'retail_price')
            ->withMin(['variants as minimum_retail_compare_at_price' => fn ($query) => $query
                ->where('is_active', true)
                ->where('is_available_retail', true)], 'retail_compare_at_price')
            ->withSum(['variants as stock_quantity' => fn ($query) => $query
                ->where('is_active', true)
                ->where('is_available_retail', true)], 'stock_quantity')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(min(max((int) $settings->new_arrivals_count, 4), 12))
            ->get();

        $featuredCategoryIds = collect($settings->featured_category_ids)->filter()->map(fn ($id): int => (int) $id)->values();
        $categories = Category::query()
            ->visibleForChannel('retail')
            ->where('is_active', true)
            ->when($featuredCategoryIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $featuredCategoryIds))
            ->whereHas('products', fn ($query) => $query
                ->where('is_active', true)
                ->whereIn('visibility', ['retail', 'both'])
                ->whereHas('variants', fn ($query) => $query
                    ->where('is_active', true)
                    ->where('is_available_retail', true)
                    ->where('stock_quantity', '>', 0)))
            ->with(['products' => fn ($query) => $query
                ->where('is_active', true)
                ->whereIn('visibility', ['retail', 'both'])
                ->whereHas('variants', fn ($query) => $query
                    ->where('is_active', true)
                    ->where('is_available_retail', true)
                    ->where('stock_quantity', '>', 0))
                ->with('primaryMedia')])
            ->when(
                $featuredCategoryIds->isNotEmpty(),
                fn ($query) => $query->orderByRaw('CASE id '.collect($featuredCategoryIds)->map(
                    fn (int $id, int $position): string => "WHEN {$id} THEN {$position}",
                )->implode(' ').' ELSE 999 END'),
                fn ($query) => $query->orderBy('position')->orderBy('name'),
            )
            ->limit(8)
            ->get();

        return Inertia::render('Home', [
            'homepage' => [
                'heroEyebrow' => $settings->hero_eyebrow,
                'heroTitle' => $settings->hero_title,
                'heroDescription' => $settings->hero_description,
                'heroImages' => $heroImages,
                'heroSliderInterval' => min(max((int) $settings->hero_slider_interval, 3), 15),
                'heroPrimaryLabel' => $settings->hero_primary_label,
                'heroPrimaryUrl' => $settings->hero_primary_url,
                'heroSecondaryLabel' => $settings->hero_secondary_label,
                'heroSecondaryUrl' => $settings->hero_secondary_url,
                'categoriesEyebrow' => $settings->catalogue_eyebrow,
                'categoriesTitle' => $settings->catalogue_title,
                'categoriesDescription' => $settings->catalogue_description,
                'showNewArrivals' => $settings->show_new_arrivals,
                'newArrivalsEyebrow' => $settings->new_arrivals_eyebrow,
                'newArrivalsTitle' => $settings->new_arrivals_title,
                'newArrivalsDescription' => $settings->new_arrivals_description,
                'metaTitle' => $settings->default_meta_title,
                'metaDescription' => $settings->default_meta_description,
            ],
            'products' => $products->map(fn (Product $product) => [
                'id' => $product->id,
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
            ]),
            'categories' => $categories->map(function (Category $category): array {
                $product = $category->products->first();

                return [
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'image' => $product?->primaryImageUrl() ?: $category->imageUrl(),
                ];
            }),
        ]);
    }
}
