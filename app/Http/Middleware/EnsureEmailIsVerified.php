<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return $next($request);
        }

        if ($request->routeIs('verification.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        return redirect()->route('verification.notice')
            ->with('info', 'Please verify your email to continue.');
    }
}