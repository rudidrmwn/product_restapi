<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->booted(function () {
        // Rate limiter: product write endpoints — max 1 hit per 5 seconds per user
        RateLimiter::for('product-write', function (Request $request) {
            return Limit::perSecond(1)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many requests. Please wait 5 seconds before trying again.',
                    ], 429);
                });
        });

        // Rate limiter: auth endpoints — max 3 hits per 60 seconds per IP
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many attempts. Please try again after 60 seconds.',
                    ], 429);
                });
        });
    })
    ->create();