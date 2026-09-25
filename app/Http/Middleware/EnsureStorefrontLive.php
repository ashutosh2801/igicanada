<?php

namespace App\Http\Middleware;

use App\Models\HomepageSetting;
use App\Support\StorefrontContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStorefrontLive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isExempt($request)) {
            return $next($request);
        }

        $settings = HomepageSetting::query()
            ->forChannel(app(StorefrontContext::class)->settingsChannel())
            ->first();

        if ($settings !== null && ! $settings->is_active) {
            return response()->view('maintenance', [
                'brandName' => $settings->brand_name,
                'metaTitle' => $settings->default_meta_title,
                'description' => $settings->default_meta_description,
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $next($request);
    }

    private function isExempt(Request $request): bool
    {
        return $request->is('admin*')
            || $request->is('up')
            || $request->routeIs('paypal.webhook');
    }
}