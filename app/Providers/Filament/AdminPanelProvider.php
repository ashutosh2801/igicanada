<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\ContactEnquiries\ContactEnquiryResource;
use App\Filament\Resources\ContentPages\ContentPageResource;
use App\Filament\Resources\HomepageSettings\HomepageSettingResource;
use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Filament\Resources\MediaFolders\MediaFolderResource;
use App\Filament\Resources\NavigationItems\NavigationItemResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\StandardShippingRates\Pages\ListStandardShippingRates;
use App\Filament\Resources\StandardShippingRates\StandardShippingRateResource;
use App\Filament\Resources\Users\UserResource;
use App\Http\Middleware\EnsureAdminStorefrontSelected;
use Filament\Enums\UserMenuPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->domain(config('storefronts.admin_domain'))
            ->login(\App\Filament\Auth\Pages\Login::class)
            ->sidebarCollapsibleOnDesktop()
            ->userMenu(position: UserMenuPosition::Topbar)
            ->colors([
                'primary' => Color::Red,
                'danger' => Color::Red,
                'info' => Color::Gray,
                'success' => Color::Red,
                'warning' => Color::Red,
            ])
            ->navigationGroups([
                'Catalog',
                'Customers',
                'Contact enquiries',
                'Content pages',
                'Appearance',
                'Media',
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                $directGroup = static fn (array $items): NavigationGroup => NavigationGroup::make()->items($items);
                $groupItems = static fn (array $items): array => array_map(
                    static fn ($item) => $item->icon(null)->activeIcon(null),
                    $items,
                );

                return $builder
                    ->group($directGroup(Dashboard::getNavigationItems()))
                    ->group(NavigationGroup::make('Catalog')->icon(Heroicon::OutlinedRectangleStack)->collapsible()->items($groupItems([
                        ...ProductResource::getNavigationItems(),
                        ...OrderResource::getNavigationItems(),
                        ...StandardShippingRateResource::getNavigationItems(),
                    ])))
                    ->group($directGroup(UserResource::getNavigationItems()))
                    ->group($directGroup(ContactEnquiryResource::getNavigationItems()))
                    ->group($directGroup(ContentPageResource::getNavigationItems()))
                    ->group(NavigationGroup::make('Appearance')->icon(Heroicon::OutlinedPaintBrush)->collapsible()->items($groupItems([
                        ...CategoryResource::getNavigationItems(),
                        ...HomepageSettingResource::getNavigationItems(),
                        ...\App\Filament\Pages\StorefrontBranding::getNavigationItems(),
                        ...NavigationItemResource::getNavigationItems(),
                    ])))
                    ->group(NavigationGroup::make('Media')->icon(Heroicon::OutlinedPhoto)->collapsible()->items($groupItems([
                        ...MediaAssetResource::getNavigationItems(),
                        ...MediaFolderResource::getNavigationItems(),
                    ])));
            })
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.styles.amazon-ember'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.styles.admin-storefront-switcher'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.styles.sidebar-compact'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.styles.shipping-rates'),
                ListStandardShippingRates::class,
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.styles.media-picker'),
                [CreateProduct::class, EditProduct::class],
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn () => view('filament.admin-storefront-switcher'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureAdminStorefrontSelected::class,
            ]);
    }
}
