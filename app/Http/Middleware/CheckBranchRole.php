<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckBranchRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has one of the allowed roles
        if (!in_array($user->role, $roles)) {
            $redirectRoute = match ($user->role) {
                'branch_manager' => 'branch.dashboard',
                'cashier' => 'branch.cashier',
                'barista' => 'branch.barista',
                'admin' => 'admin.dashboard',
                'owner', 'shop_owner', 'tenant_owner' => 'owner.dashboard',
                'customer' => 'customer.dashboard',
                default => null,
            };

            if (!$redirectRoute) {
                abort(403, 'You do not have permission to access that page.');
            }

            return redirect()->route($redirectRoute)
                ->with('error', 'You do not have permission to access that page.');
        }

        return $next($request);
    }
}
