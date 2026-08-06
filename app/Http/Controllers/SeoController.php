<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContentPage;
use App\Models\Product;
use App\Support\StorefrontContext;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $channel = app(StorefrontContext::class)->channel;
        $baseUrl = 'https://'.config("storefronts.{$channel}.domain");
        $listingPath = $channel === 'retail' ? '/shop' : '/catalogue';
        $productPrefix = $channel === 'retail' ? '/products/' : '/catalogue/';
        $pagePrefix = $channel === 'retail' ? '/policies/' : '/page/';
        $visibility = $channel === 'retail' ? ['retail', 'both'] : ['wholesale', 'both'];

        $urls = collect([
            ['loc' => $baseUrl.'/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => $baseUrl.$listingPath, 'priority' => '0.9', 'changefreq' => 'daily'],
        ])->concat(
            Category::query()->visibleForChannel($channel)->where('is_active', true)->get(['slug', 'updated_at'])->map(fn (Category $category): array => [
                'loc' => $baseUrl.$listingPath.'?category='.rawurlencode($category->slug),
                'lastmod' => $category->updated_at?->toAtomString(),
                'priority' => '0.7',
                'changefreq' => 'weekly',
            ]),
        )->concat(
            Product::query()->where('is_active', true)->whereIn('visibility', $visibility)->get(['slug', 'updated_at'])->map(fn (Product $product): array => [
                'loc' => $baseUrl.$productPrefix.rawurlencode($product->slug),
                'lastmod' => $product->updated_at?->toAtomString(),
                'priority' => '0.8',
                'changefreq' => 'weekly',
            ]),
        )->concat(
            ContentPage::query()->forChannel($channel)->published()->get(['slug', 'updated_at'])->map(fn (ContentPage $page): array => [
                'loc' => $baseUrl.$pagePrefix.rawurlencode($page->slug),
                'lastmod' => $page->updated_at?->toAtomString(),
                'priority' => '0.5',
                'changefreq' => 'monthly',
            ]),
        );

        return response()->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $channel = app(StorefrontContext::class)->channel;
        $baseUrl = 'https://'.config("storefronts.{$channel}.domain");
        $disallow = $channel === 'retail'
            ? ['/cart', '/checkout', '/orders/', '/payments/']
            : ['/admin', '/account', '/cart', '/checkout', '/orders', '/login', '/forgot-password', '/reset-password', '/payments/'];

        $lines = ['User-agent: *', 'Allow: /'];
        foreach ($disallow as $path) {
            $lines[] = 'Disallow: '.$path;
        }
        $lines[] = 'Sitemap: '.$baseUrl.'/sitemap.xml';

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
