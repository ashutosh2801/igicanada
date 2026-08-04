<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContentPage;
use App\Models\Product;
use App\Support\StorefrontAsset;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query = trim((string) $request->string('q'));
        $searching = mb_strlen($query) >= 2;
        $user = $request->user()?->loadMissing('priceTier');
        $canViewPricing = $user?->isApprovedWholesale() ?? false;
        $discount = $canViewPricing ? (float) ($user->priceTier?->discount_percentage ?? 0) : 0;

        $products = $searching ? Product::query()
            ->where('is_active', true)
            ->where('visibility', 'wholesale')
            ->where(fn ($builder) => $builder
                ->where('name', 'like', "%{$query}%")
                ->orWhere('sku', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%"))
            ->withMin(['variants as minimum_wholesale_price' => fn ($builder) => $builder->where('is_active', true)], 'wholesale_price')
            ->orderBy('name')
            ->limit(24)
            ->get()
            ->map(fn (Product $product) => [
                'type' => 'Product',
                'title' => $product->name,
                'description' => $product->sku,
                'url' => route('catalogue.show', $product, false),
                'image' => StorefrontAsset::legacy($product->primary_image_path),
                'accountPrice' => $canViewPricing && $product->minimum_wholesale_price !== null
                    ? number_format((float) $product->minimum_wholesale_price * (1 - $discount / 100), 2, '.', '')
                    : null,
            ]) : collect();

        $categories = $searching ? Category::query()
            ->where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->map(fn (Category $category) => [
                'type' => 'Category',
                'title' => $category->name,
                'description' => null,
                'url' => route('catalogue.index', ['category' => $category->slug], false),
                'image' => StorefrontAsset::legacy($category->image_path, 'category'),
                'accountPrice' => null,
            ]) : collect();

        $pages = $searching ? ContentPage::published()
            ->where(fn ($builder) => $builder
                ->where('title', 'like', "%{$query}%")
                ->orWhere('excerpt', 'like', "%{$query}%"))
            ->orderBy('title')
            ->limit(12)
            ->get()
            ->map(fn (ContentPage $page) => [
                'type' => 'Page',
                'title' => $page->title,
                'description' => $page->excerpt,
                'url' => route('pages.show', $page->slug, false),
                'image' => null,
                'accountPrice' => null,
            ]) : collect();

        return Inertia::render('Search/Index', [
            'query' => $query,
            'results' => $products->concat($categories)->concat($pages)->values(),
            'pricingAuthorized' => $canViewPricing,
        ]);
    }
}
