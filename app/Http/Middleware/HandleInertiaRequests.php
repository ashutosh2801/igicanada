<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\HomepageSetting;
use App\Models\NavigationItem;
use App\Support\StorefrontAsset;
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
        $settings = HomepageSetting::query()->first();
        $navigation = NavigationItem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('location');
        $categoriesByParent = Category::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug'])
            ->groupBy(fn (Category $category) => (int) ($category->parent_id ?? 0));
        $user = $request->user();
        $cartCount = $user?->isApprovedWholesale()
            ? (int) $user->cart?->items()->sum('quantity')
            : 0;
        $accountNavigation = match (true) {
            $user?->account_type === 'admin' && $user->approval_status === 'approved' => [
                'label' => 'Admin panel',
                'url' => '/admin',
            ],
            $user?->isApprovedWholesale() => [
                'label' => 'Account',
                'url' => route('account.dashboard', [], false),
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
            'auth' => [
                'user' => $request->user()?->only([
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
                'headerNavigation' => ($navigation->get('header') ?? collect())->map->only([
                    'label', 'url', 'opens_new_tab',
                ])->values(),
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
}
