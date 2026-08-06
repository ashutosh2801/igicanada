<?php

namespace App\Http\Middleware;

use App\Support\StorefrontContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectStorefront
{
    public function handle(Request $request, Closure $next): Response
    {
        $storefront = StorefrontContext::fromRequest($request);
        app()->instance(StorefrontContext::class, $storefront);
        $request->attributes->set('sales_channel', $storefront->channel);

        if ($storefront->isRetail() && $storefront->domain !== config('storefronts.retail.domain')) {
            return redirect()->to('https://'.config('storefronts.retail.domain').'/'.ltrim($request->getRequestUri(), '/'), 301);
        }

        if ($storefront->isRetail() && ! $request->routeIs('retail.*')) {
            abort(404);
        }

        return $next($request);
    }
}
