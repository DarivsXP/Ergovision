<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // If logged in but hasn't picked a role, and isn't already on the selection page
        if (auth()->check() && empty(auth()->user()->user_type) && !$request->routeIs('onboarding.*')) {
            return redirect()->route('onboarding.index');
        }

        return $next($request);
    }
}
