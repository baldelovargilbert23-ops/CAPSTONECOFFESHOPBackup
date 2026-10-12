<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Branch;

class BranchAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Send people who do not work at a branch back to their own area.
        $homeRoute = match ($user->role) {
            'admin' => 'admin.dashboard',
            'owner', 'shop_owner', 'tenant_owner' => 'owner.dashboard',
            'customer' => 'customer.dashboard',
            default => null,
        };

        if ($homeRoute) {
            return redirect()->route($homeRoute);
        }

        if (!in_array($user->role, ['branch_manager', 'cashier', 'barista'], true)) {
            abort(403, 'Unauthorized: Branch staff access required.');
        }

        if (!$user->is_active) {
            abort(403, 'Your account is inactive. Please contact the administrator.');
        }

        if (!$user->branch_id || !$user->tenant_id) {
            abort(403, 'No branch assigned to this user.');
        }

        $branch = Branch::where('tenant_id', $user->tenant_id)->find($user->branch_id);
        if (!$branch) {
            abort(403, 'Your assigned branch no longer exists.');
        }

        if (!$branch->is_active) {
            abort(403, 'Your assigned branch is inactive.');
        }

        if ($branch->approval_status !== 'approved') {
            abort(403, 'Your assigned branch is not approved yet.');
        }

        session(['branch_id' => $branch->id, 'tenant_id' => $branch->tenant_id]);

        return $next($request);
    }
}
