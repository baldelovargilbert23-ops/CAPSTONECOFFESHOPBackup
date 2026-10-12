<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user has customer role using the role column
        if ($user->role === 'customer') {
            return $next($request);
        }

        // If admin, redirect to admin dashboard
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // If owner, redirect to owner dashboard
        if ($user->role === 'owner') {
            return redirect()->route('owner.dashboard');
        }

        // If branch staff, redirect to branch dashboard
        if (in_array($user->role, ['branch_manager', 'cashier', 'barista'])) {
            return redirect()->route('branch.dashboard');
        }

        // If no valid role, redirect to login
        return redirect()->route('login')->with('error', 'Unauthorized access.');
    }
}