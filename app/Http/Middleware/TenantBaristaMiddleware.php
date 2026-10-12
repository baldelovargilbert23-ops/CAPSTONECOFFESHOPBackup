<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantBaristaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Admin / Owner can preview
        if (in_array($user->role, ['admin', 'owner'], true)) {
            return $next($request);
        }

        // Must be barista
        if ($user->role !== 'barista') {
            abort(403, 'Unauthorized: Barista access required.');
        }

        // Must belong to a tenant
        if (! $user->tenant_id) {
            abort(403, 'No tenant assigned to your account.');
        }

        // This is the TENANT barista page → only main-store baristas
        // (branch_id = null). Branch baristas go to their own page.
        if ($user->branch_id !== null) {
            return redirect()->route('branch.barista');
        }

        session()->forget('branch_id');
        session(['tenant_id' => $user->tenant_id]);

        return $next($request);
    }
}