<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CashierMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        if (!$user->is_active) {
            abort(403, 'Your account is inactive.');
        }

        if ($user->role !== 'cashier') {
            $homeRoute = match ($user->role) {
                'admin' => 'admin.dashboard',
                'owner', 'shop_owner', 'tenant_owner' => 'owner.dashboard',
                'branch_manager' => 'branch.dashboard',
                'barista' => $user->branch_id ? 'branch.barista' : 'tenant.barista',
                'customer' => 'customer.dashboard',
                default => null,
            };

            if ($homeRoute) {
                return redirect()->route($homeRoute);
            }

            abort(403, 'Cashier access is required.');
        }

        if (!$user->tenant_id) {
            abort(403, 'No coffee shop is assigned to this account.');
        }

        $tenant = Tenant::find($user->tenant_id);
        if (!$tenant || $tenant->status !== 'active') {
            abort(403, 'This shop is not active.');
        }

        // /cashier is for the main store. Branch cashiers use /branch/cashier.
        if ($user->branch_id) {
            $branch = Branch::where('tenant_id', $user->tenant_id)->find($user->branch_id);
            if (!$branch || !$branch->is_active || $branch->approval_status !== 'approved') {
                abort(403, 'Your assigned branch is inactive or not approved.');
            }

            return redirect()->route('branch.cashier');
        }

        // NULL branch_id is the tenant's main store, not an unassigned cashier.
        session(['tenant_id' => $user->tenant_id, 'branch_id' => null]);

        return $next($request);
    }
}
