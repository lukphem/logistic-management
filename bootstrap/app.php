<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'user_type' => \App\Http\Middleware\UserType::class,
            'staff' => \App\Http\Middleware\EnsureStaffUser::class,
            'client-portal' => \App\Http\Middleware\EnsureClientUser::class,
        ]);

        // Paystack's own webhook POST carries no CSRF token — it's not
        // a browser form submission, it's a server-to-server call from
        // Paystack's infrastructure. Trust for this route comes from
        // signature verification inside the controller itself
        // (PaystackService::verifyWebhookSignature), not from CSRF.
        $middleware->validateCsrfTokens(except: [
            'payments/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
