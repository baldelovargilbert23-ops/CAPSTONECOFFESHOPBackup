<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OwnerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Admin goes to admin dashboard
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // Customer goes to customer area
        if ($user->role === 'customer') {
            return redirect()->route('customer.dashboard');
        }

        // Branch staff goes to branch dashboard
        if (in_array($user->role, ['branch_manager', 'cashier', 'barista'])) {
            return redirect()->route('branch.dashboard');
        }

        // Owners (any variant) pass through
        if (! in_array($user->role, ['owner', 'shop_owner', 'tenant_owner'], true)) {
            abort(403, 'Unauthorized: Owner access required.');
        }

        return $next($request);
    }
}