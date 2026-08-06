<?php

namespace App\Http\Middleware;

use App\Support\AdminStorefront;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminStorefrontSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has(AdminStorefront::SESSION_KEY)) {
            return $next($request);
        }

        $remembered = $request->user()?->admin_sales_channel;

        if (array_key_exists((string) $remembered, AdminStorefront::options())) {
            AdminStorefront::select($remembered);

            return $next($request);
        }

        return redirect()->route('admin.storefront.select');
    }
}
