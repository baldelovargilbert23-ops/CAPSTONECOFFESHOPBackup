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
        // Added by EnvKit so shared (public) URLs keep the https scheme and
        // public host. Safe locally; remove to opt out.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin'          => App\Http\Middleware\AdminMiddleware::class,
            'owner'          => App\Http\Middleware\OwnerMiddleware::class,
            'employee'       => App\Http\Middleware\EmployeeMiddleware::class,
            'customer'       => App\Http\Middleware\CustomerMiddleware::class,
            'cashier'        => App\Http\Middleware\CashierMiddleware::class,
            'barista'        => App\Http\Middleware\BaristaMiddleware::class,
            'branch.access'  => App\Http\Middleware\BranchAccess::class,
            'branch.role'    => App\Http\Middleware\CheckBranchRole::class,

            // ✅ Tenant-level barista (main store, branch_id = null)
            'tenant.barista' => App\Http\Middleware\TenantBaristaMiddleware::class,

            // ✅ Email verification middleware
            'verified.custom' => App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();