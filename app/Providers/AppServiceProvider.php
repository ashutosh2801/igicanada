<?php

namespace App\Providers;

use App\Support\StorefrontContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(StorefrontContext::class, function ($app): StorefrontContext {
            return StorefrontContext::fromRequest($app->make('request'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
