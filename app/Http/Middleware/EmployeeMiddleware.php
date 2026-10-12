<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EmployeeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Must be logged in
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user->tenant_id;

        // 2. Allow Admins to pass through
        if ($user->role === 'admin') {
            return $next($request);
        }

        // 3. Check if user is active
        if (!$user->is_active) {
            abort(403, 'Your account is inactive. Please contact the administrator.');
        }

        // 4. Check if user belongs to the tenant
        if ($user->tenant_id != $tenantId) {
            return redirect()->route('login')->with('error', 'Unauthorized: You do not belong to this shop.');
        }

        // 5. Check if tenant is active
        $tenant = Tenant::find($tenantId);
        if (!$tenant || $tenant->status !== 'active') {
            return redirect()->route('login')->with('error', 'This shop is not active.');
        }

        // 6. Check if user has employee role (branch_manager, cashier, barista) or owner
        $allowedRoles = ['branch_manager', 'cashier', 'barista', 'owner'];
        
        if (!in_array($user->role, $allowedRoles)) {
            abort(403, 'Unauthorized: You are not an employee of this shop.');
        }

        // 7. For branch_manager, cashier, barista - check if they have a branch assigned
        if (in_array($user->role, ['branch_manager', 'cashier', 'barista'])) {
            if (!$user->branch_id) {
                abort(403, 'No branch assigned to your account.');
            }

            // Check if branch is active
            $branch = Branch::find($user->branch_id);
            if (!$branch || !$branch->is_active) {
                abort(403, 'Your assigned branch is inactive.');
            }
        }

        // Store branch_id in session
        if ($user->branch_id) {
            session(['branch_id' => $user->branch_id]);
        }

        return $next($request);
    }
}