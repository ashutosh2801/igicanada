<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HomepageSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\StorefrontAsset;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $settings = HomepageSetting::query()->firstOrFail();
        $user = $request->user()?->loadMissing('priceTier');
        $canViewPricing = $user?->isApprovedWholesale() ?? false;
        $discount = $canViewPricing ? (float) ($user->priceTier?->discount_percentage ?? 0) : 0;
        $featuredIds = collect($settings->featured_category_ids)->map(fn ($id) => (int) $id)->filter();
        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('products', fn ($query) => $query
                ->where('is_active', true)
                ->where('visibility', 'wholesale'))
            ->when($featuredIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $featuredIds))
            ->withCount(['products' => fn ($query) => $query
                ->where('is_active', true)
                ->where('visibility', 'wholesale')])
            ->orderBy('position')
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->when($featuredIds->isNotEmpty(), fn ($items) => $items->sortBy(
                fn (Category $category) => $featuredIds->search($category->id)
            )->values());

        $fallbackHero = Product::query()
            ->where('is_active', true)
            ->where('visibility', 'wholesale')
            ->whereNotNull('primary_image_path')
            ->orderByDesc('published_at')
            ->value('primary_image_path');

        $heroImages = collect($settings->hero_image_paths)
            ->map(fn ($path) => StorefrontAsset::uploaded($path))
            ->filter()
            ->values();
        if ($heroImages->isEmpty()) {
            $fallbackImage = StorefrontAsset::uploaded($settings->hero_image_path)
                ?: StorefrontAsset::legacy($fallbackHero);
            if ($fallbackImage) {
                $heroImages->push($fallbackImage);
            }
        }

        $newArrivals = collect();

        if ($settings->show_new_arrivals) {
            $newArrivals = Product::query()
                ->select(['id', 'sku', 'name', 'slug', 'primary_image_path', 'published_at'])
                ->where('is_active', true)
                ->where('visibility', 'wholesale')
                ->withCount(['variants' => fn ($query) => $query->where('is_active', true)])
                ->withSum(['variants as stock_quantity' => fn ($query) => $query->where('is_active', true)], 'stock_quantity')
                ->withMin(['variants as minimum_wholesale_price' => fn ($query) => $query->where('is_active', true)], 'wholesale_price')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(min(max((int) $settings->new_arrivals_count, 4), 12))
                ->get();
        }

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
                'catalogueEyebrow' => $settings->catalogue_eyebrow,
                'catalogueTitle' => $settings->catalogue_title,
                'catalogueDescription' => $settings->catalogue_description,
                'showNewArrivals' => $settings->show_new_arrivals,
                'newArrivalsEyebrow' => $settings->new_arrivals_eyebrow,
                'newArrivalsTitle' => $settings->new_arrivals_title,
                'newArrivalsDescription' => $settings->new_arrivals_description,
            ],
            'categories' => $categories->map(fn (Category $category) => [
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => trim(strip_tags((string) $category->description)),
                'image' => StorefrontAsset::legacy($category->image_path, 'category'),
                'products' => $category->products_count,
            ]),
            'newArrivals' => $newArrivals->map(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => StorefrontAsset::legacy($product->primary_image_path),
                'variants' => $product->variants_count,
                'inStock' => (int) $product->stock_quantity > 0,
                'wholesalePrice' => $canViewPricing && $product->minimum_wholesale_price !== null
                    ? number_format((float) $product->minimum_wholesale_price, 2, '.', '')
                    : null,
                'accountPrice' => $canViewPricing && $product->minimum_wholesale_price !== null
                    ? number_format((float) $product->minimum_wholesale_price * (1 - $discount / 100), 2, '.', '')
                    : null,
            ]),
            'pricing' => [
                'authorized' => $canViewPricing,
                'tier' => $canViewPricing ? $user->priceTier?->name : null,
            ],
            'catalogue' => [
                'categories' => Category::where('is_active', true)->count(),
                'products' => Product::where('is_active', true)->where('visibility', 'wholesale')->count(),
                'variants' => ProductVariant::where('is_active', true)
                    ->whereHas('product', fn ($query) => $query->where('is_active', true)->where('visibility', 'wholesale'))
                    ->count(),
            ],
        ]);
    }
}
