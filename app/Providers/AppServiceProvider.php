<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Observers\BranchObserver;
use App\Observers\OrderObserver;
use App\Observers\TenantObserver;
use App\Observers\UserObserver;
use App\Policies\StaffPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): array {
            $email = $request->input('email');
            $identity = is_string($email) ? mb_strtolower(trim($email)) : '';

            return [
                Limit::perMinute(30)->by('login-ip:'.$request->ip()),
                Limit::perMinute(5)->by('login-email:'.hash('sha256', $identity)),
            ];
        });

        RateLimiter::for('email-availability', fn (Request $request) =>
            Limit::perMinute(20)->by('email-availability:'.$request->ip())
        );

        // Observers
        Tenant::observe(TenantObserver::class);
        Branch::observe(BranchObserver::class);
        User::observe(UserObserver::class);
        Order::observe(OrderObserver::class);

        // Laravel 11 auto-discovers most policies by convention,
        // but "User" → "StaffPolicy" needs an explicit mapping.
        Gate::policy(User::class, StaffPolicy::class);

        // Super-admin bypass — admin role passes every gate
        Gate::before(function ($user, $ability) {
            if ($user->role === 'admin') {
                return true;
            }
        });
    }
}
