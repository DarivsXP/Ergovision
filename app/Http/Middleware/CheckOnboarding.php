<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOnboarding
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // STRICT CHECK: Even if is_onboarded is true, if they have no age or occupation, force them back!
            if (!$user->is_onboarded || is_null($user->age) || is_null($user->occupation)) {
                
                // Allow them to access the onboarding page or logout so we don't cause an infinite redirect loop
                if (!$request->routeIs('onboarding.*') && !$request->routeIs('logout')) {
                    return redirect()->route('onboarding.create');
                }
            }
        }

        return $next($request);
    }
}