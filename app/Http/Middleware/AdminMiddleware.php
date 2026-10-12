<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is logged in and has admin role using the role column
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }
        
        abort(403, 'Unauthorized: Admin access required.');
    }
}