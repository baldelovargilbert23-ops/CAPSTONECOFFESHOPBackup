<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BaristaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Allow admin and owner to access
        if (in_array($user->role, ['admin', 'owner'])) {
            return $next($request);
        }

        // Check if user is barista
        if ($user->role !== 'barista') {
            abort(403, 'Unauthorized: Barista access required.');
        }

        // Check if barista has a branch assigned
        if (is_null($user->branch_id)) {
            abort(403, 'No branch assigned to this user.');
        }

        return $next($request);
    }
}