<?php

use App\Http\Middleware\DetectStorefront;
use App\Http\Middleware\EnsureApprovedReseller;
use App\Http\Middleware\HandleInertiaRequests;
use App\Support\StorefrontContext;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'payments/paypal/webhook',
        ]);
        $middleware->alias([
            'approved.wholesale' => EnsureApprovedReseller::class,
        ]);
        $middleware->web(append: [
            DetectStorefront::class,
            HandleInertiaRequests::class,
        ]);

        Authenticate::redirectUsing(function (Request $request) {
            if (StorefrontContext::fromRequest($request)->isRetail()) {
                return route('retail.account.login', [], false);
            }

            return route('login', [], false);
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
