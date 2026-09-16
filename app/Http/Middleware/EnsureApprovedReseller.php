<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedReseller
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user('web')?->isApprovedWholesale(), 403, 'An approved wholesale account is required.');

        return $next($request);
    }
}
