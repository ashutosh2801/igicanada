<?php

use App\Http\Controllers\Admin\StorefrontSelectionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\ResellerApplicationController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContentPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\PayPalWebhookController;
use App\Http\Controllers\Retail\CartController as RetailCartController;
use App\Http\Controllers\Retail\CatalogueController as RetailCatalogueController;
use App\Http\Controllers\Retail\CheckoutController as RetailCheckoutController;
use App\Http\Controllers\Retail\ContentPageController as RetailContentPageController;
use App\Http\Controllers\Retail\HomeController as RetailHomeController;
use App\Http\Controllers\Retail\OrderController as RetailOrderController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::domain(config('storefronts.admin_domain'))
    ->prefix('admin/storefront')
    ->name('admin.storefront.')
    ->middleware('auth')
    ->group(function (): void {
        Route::get('/select', [StorefrontSelectionController::class, 'create'])->name('select');
        Route::post('/select', [StorefrontSelectionController::class, 'store'])->name('store');
    });

Route::domain(config('storefronts.retail.domain'))
    ->name('retail.')
    ->group(function (): void {
        Route::get('/', RetailHomeController::class)->name('home');
        Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
        Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
        Route::get('/shop', [RetailCatalogueController::class, 'index'])->name('catalogue.index');
        Route::get('/products/{product:slug}', [RetailCatalogueController::class, 'show'])->name('catalogue.show');
        Route::get('/cart', [RetailCartController::class, 'index'])->name('cart.index');
        Route::post('/cart/items', [RetailCartController::class, 'store'])->name('cart.store');
        Route::put('/cart/items/{item}', [RetailCartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/items/{item}', [RetailCartController::class, 'destroy'])->name('cart.destroy');
        Route::get('/checkout', [RetailCheckoutController::class, 'create'])->name('checkout.create');
        Route::post('/checkout', [RetailCheckoutController::class, 'store'])->name('checkout.store');
        Route::get('/orders/{order}', [RetailOrderController::class, 'show'])->name('orders.show');
        Route::get('/policies/{slug}', RetailContentPageController::class)->name('pages.show');
        Route::post('/orders/{order}/paypal', [PayPalController::class, 'create'])->name('paypal.create');
        Route::get('/payments/paypal/return', [PayPalController::class, 'capture'])->name('paypal.return');
        Route::get('/payments/paypal/cancel', [PayPalController::class, 'cancel'])->name('paypal.cancel');
    });

Route::get('/', HomeController::class)->name('home');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::get('/catalogue', CatalogueController::class)->name('catalogue.index');
Route::get('/catalogue/{product:slug}', [CatalogueController::class, 'show'])->name('catalogue.show');
Route::get('/search', SearchController::class)->middleware('throttle:30,1')->name('search');
Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
Route::get('/page/{slug}', ContentPageController::class)->name('pages.show');
Route::post('/payments/paypal/webhook', PayPalWebhookController::class)->name('paypal.webhook');
Route::get('/wholesale/apply', [ResellerApplicationController::class, 'create'])->name('wholesale.apply');
Route::get('/wholesale/application-received', [ResellerApplicationController::class, 'confirmation'])->name('wholesale.confirmation');
Route::get('/wholesale/verify-email/{id}/{hash}', [ResellerApplicationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
    Route::post('/wholesale/apply', [ResellerApplicationController::class, 'store'])->middleware('throttle:5,1')->name('wholesale.store');
    Route::post('/wholesale/resend-verification', [ResellerApplicationController::class, 'resend'])->middleware('throttle:3,1')->name('verification.send');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/orders/{order}/invoice', InvoiceController::class)->name('orders.invoice');
    Route::get('/account/status', function () {
        $user = request()->user();

        return Inertia::render('Account/Status', ['account' => [
            'name' => $user->name,
            'email' => $user->email,
            'accountType' => $user->account_type,
            'approvalStatus' => $user->approval_status,
        ]]);
    })->name('account.status');
});

Route::middleware(['auth', 'approved.wholesale'])->group(function (): void {
    Route::get('/account', function () {
        $user = request()->user()->load('priceTier', 'resellerProfile');

        return Inertia::render('Account/Dashboard', ['account' => [
            'name' => $user->name,
            'email' => $user->email,
            'accountType' => $user->account_type,
            'approvalStatus' => $user->approval_status,
            'company' => $user->resellerProfile?->company,
            'priceTier' => $user->priceTier?->name,
            'discountPercentage' => $user->priceTier?->discount_percentage,
        ]]);
    })->name('account.dashboard');
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/items', [CartController::class, 'store'])->name('cart.store');
    Route::put('/cart/items/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout/shipping-rates', [CheckoutController::class, 'rates'])->middleware('throttle:20,1')->name('checkout.shipping-rates');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/paypal', [PayPalController::class, 'create'])->name('paypal.create');
    Route::get('/payments/paypal/return', [PayPalController::class, 'capture'])->name('paypal.return');
    Route::get('/payments/paypal/cancel', [PayPalController::class, 'cancel'])->name('paypal.cancel');
});
