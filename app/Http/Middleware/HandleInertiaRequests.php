<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\ContentPage;
use App\Models\HomepageSetting;
use App\Models\NavigationItem;
use App\Services\RetailCartService;
use App\Support\StorefrontAsset;
use App\Support\StorefrontContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function rootView(Request $request): string
    {
        return app(StorefrontContext::class)->isRetail() ? 'retail' : $this->rootView;
    }

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        if (app(StorefrontContext::class)->isRetail()) {
            $settings = HomepageSetting::query()->forChannel('retail')->first();
            $retailCart = app(RetailCartService::class)->summary($request);

            return [
                ...parent::share($request),
                'salesChannel' => 'retail',
                'seoDefaults' => $this->seoDefaults($settings, 'retail'),
                'auth' => [
                    'user' => $request->user()
                        ? [
                            'id' => $request->user()->id,
                            'name' => $request->user()->name,
                            'email' => $request->user()->email,
                            'avatar' => $request->user()->avatarUrl(),
                        ]
                        : null,
                ],
                'flash' => [
                    'status' => fn () => $request->session()->get('status'),
                ],
                'retailStorefront' => [
                    'brandName' => $settings?->brand_name ?? config('storefronts.retail.name'),
                    'logoUrl' => StorefrontAsset::uploaded($settings?->logo_path),
                    'logoAlt' => $settings?->logo_alt ?? config('storefronts.retail.name'),
                    'announcement' => $settings?->announcement_text,
                    'cartCount' => $retailCart['count'],
                    'cartSummary' => [
                        'items' => $retailCart['items'],
                        'subtotal' => $retailCart['subtotal'],
                    ],
                    'footer' => [
                        'description' => $settings?->footer_description,
                        'address' => $settings?->footer_address,
                        'phone' => $settings?->footer_phone,
                        'email' => $settings?->footer_email,
                        'copyright' => $settings?->footer_copyright,
                    ],
                    'legalNavigation' => ContentPage::query()
                        ->forChannel('retail')
                        ->published()
                        ->where('is_legal', true)
                        ->orderBy('title')
                        ->get(['title', 'slug'])
                        ->map(fn (ContentPage $page) => [
                            'label' => $page->title,
                            'url' => route('retail.pages.show', $page->slug, false),
                        ]),
                ],
            ];
        }

        $settings = HomepageSetting::query()->forChannel('wholesale')->first();
        $navigation = NavigationItem::query()
            ->where('sales_channel', 'wholesale')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('location');
        $categoriesByParent = Category::query()
            ->visibleForChannel('wholesale')
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug'])
            ->groupBy(fn (Category $category) => (int) ($category->parent_id ?? 0));
        $user = $request->user('web');

        if ($user?->account_type === 'admin') {
            $user = null;
        }

        $cart = $user?->isApprovedWholesale()
            ? $user->cart()->with('items.variant.product.primaryMedia')->first()
            : null;
        $cartCount = (int) ($cart?->items->sum('quantity') ?? 0);
        $discount = (float) ($user?->priceTier?->discount_percentage ?? 0);
        $cartSubtotal = 0;
        $cartItems = $cart?->items->map(function ($item) use ($discount, &$cartSubtotal): array {
            $unitPrice = round((float) $item->variant->wholesale_price * (1 - $discount / 100), 2);
            $lineTotal = round($unitPrice * $item->quantity, 2);
            $cartSubtotal += $lineTotal;

            return [
                'id' => $item->id,
                'product' => $item->variant->product->name,
                'slug' => $item->variant->product->slug,
                'option' => $item->variant->optionLabel(),
                'image' => $item->variant->product->primaryImageUrl(),
                'quantity' => $item->quantity,
                'unitPrice' => number_format($unitPrice, 2, '.', ''),
                'lineTotal' => number_format($lineTotal, 2, '.', ''),
            ];
        })->values()->all() ?? [];
        $firstName = $user?->isApprovedWholesale()
            ? trim((string) str((string) $user->name)->before(' '))
            : null;
        $accountNavigation = match (true) {
            $user?->isApprovedWholesale() => [
                'label' => $firstName !== '' ? $firstName : 'Account',
                'url' => route('account.dashboard', [], false),
                'items' => [
                    ['label' => 'Orders', 'url' => route('orders.index', [], false)],
                    ['label' => 'Addresses', 'url' => route('account.addresses.index', [], false)],
                    ['label' => 'Profile', 'url' => route('account.dashboard', [], false)],
                    ['label' => 'Logout', 'url' => route('logout', [], false), 'method' => 'post'],
                ],
            ],
            $user !== null => [
                'label' => 'Account status',
                'url' => route('account.status', [], false),
            ],
            default => [
                'label' => 'Wholesale login',
                'url' => route('login', [], false),
            ],
        };

        return [
            ...parent::share($request),
            'salesChannel' => app(StorefrontContext::class)->channel,
            'seoDefaults' => $this->seoDefaults($settings, 'wholesale'),
            'auth' => [
                'user' => $user?->only([
                    'id',
                    'name',
                    'email',
                    'account_type',
                    'approval_status',
                ]),
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
            ],
            'storefront' => [
                'brandName' => $settings?->brand_name ?? 'IGI Canada',
                'logoUrl' => StorefrontAsset::uploaded($settings?->logo_path),
                'logoAlt' => $settings?->logo_alt ?? 'IGI Canada',
                'announcement' => $settings?->announcement_text,
                'showCategoryMenu' => $settings?->show_category_menu ?? true,
                'categoryMenuLabel' => $settings?->category_menu_label ?? 'All categories',
                'categoryNavigation' => $this->categoryTree($categoriesByParent),
                'headerNavigation' => ($navigation->get('header') ?? collect())
                    ->map(fn ($item): array => is_array($item) ? $item : $item->only([
                        'label', 'url', 'opens_new_tab',
                    ]))->values(),
                'footerNavigation' => ($navigation->get('footer') ?? collect())->map->only([
                    'label', 'url', 'opens_new_tab',
                ])->values(),
                'footer' => [
                    'description' => $settings?->footer_description,
                    'address' => $settings?->footer_address,
                    'phone' => $settings?->footer_phone,
                    'email' => $settings?->footer_email,
                    'copyright' => $settings?->footer_copyright,
                ],
                'accountNavigation' => $accountNavigation,
                'cartNavigation' => [
                    'url' => $user?->isApprovedWholesale()
                        ? route('cart.index', [], false)
                        : ($user === null ? route('login', [], false) : null),
                    'available' => $user?->isApprovedWholesale() ?? false,
                ],
                'cartCount' => $cartCount,
                'cartSummary' => [
                    'items' => $cartItems,
                    'subtotal' => number_format($cartSubtotal, 2, '.', ''),
                ],
                'seo' => [
                    'title' => $settings?->default_meta_title ?? 'IGI Canada Wholesale Leather Goods',
                    'description' => $settings?->default_meta_description,
                    'faviconUrl' => StorefrontAsset::uploaded($settings?->favicon_path),
                    'ogTitle' => $settings?->og_title,
                    'ogDescription' => $settings?->og_description,
                    'ogImageUrl' => $this->absoluteUrl(StorefrontAsset::uploaded($settings?->og_image_path)),
                    'twitterCard' => $settings?->twitter_card ?? 'summary_large_image',
                    'twitterTitle' => $settings?->twitter_title,
                    'twitterDescription' => $settings?->twitter_description,
                    'twitterImageUrl' => $this->absoluteUrl(StorefrontAsset::uploaded($settings?->twitter_image_path)),
                ],
            ],
        ];
    }

    private function categoryTree(Collection $categoriesByParent, int $parentId = 0, int $depth = 1): array
    {
        if ($depth > 3) {
            return [];
        }

        return ($categoriesByParent->get($parentId) ?? collect())
            ->map(fn (Category $category) => [
                'name' => $category->name,
                'slug' => $category->slug,
                'url' => route('catalogue.index', ['category' => $category->slug], false),
                'children' => $this->categoryTree($categoriesByParent, $category->id, $depth + 1),
            ])
            ->values()
            ->all();
    }

    private function absoluteUrl(?string $path): ?string
    {
        return $path ? url($path) : null;
    }

    private function seoDefaults(?HomepageSetting $settings, string $channel): array
    {
        $baseUrl = 'https://'.config("storefronts.{$channel}.domain");
        $image = StorefrontAsset::uploaded($settings?->og_image_path);

        return [
            'siteName' => config("storefronts.{$channel}.name"),
            'baseUrl' => $baseUrl,
            'defaultTitle' => $settings?->default_meta_title ?? config("storefronts.{$channel}.name"),
            'defaultDescription' => $settings?->default_meta_description,
            'defaultImage' => $image ? (str_starts_with($image, 'http') ? $image : $baseUrl.'/'.ltrim($image, '/')) : null,
        ];
    }
}
