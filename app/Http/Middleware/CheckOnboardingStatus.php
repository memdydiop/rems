<?php

namespace App\Http\Middleware;

use App\Models\Property;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOnboardingStatus
{
    /**
     * Redirect to onboarding if user has no properties.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if already on onboarding or auth routes
        if (
            $request->routeIs('tenant.onboarding') ||
            $request->routeIs('tenant.settings.billing') ||
            $request->routeIs('tenant.billing.callback') ||
            $request->routeIs('login') ||
            $request->routeIs('logout') ||
            $request->is('livewire/*')
        ) {
            return $next($request);
        }

        // Skip for API requests
        if ($request->expectsJson()) {
            return $next($request);
        }

        // Check if user is authenticated and tenant context is active
        if (auth()->check() && tenant()) {
            $user = auth()->user();

            // Redirect to onboarding if they haven't completed it and have no properties
            if (!$user->has_completed_onboarding && !Property::exists()) {
                return redirect()->route('tenant.onboarding');
            }
        }

        return $next($request);
    }
}
