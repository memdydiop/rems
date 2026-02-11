<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenancy()->tenant;

        if (!$tenant) {
            return $next($request);
        }

        // Check 1: Is in Free Trial?
        if ($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture()) {
            return $next($request);
        }

        $subscription = $tenant->subscription;

        // Check 2: Has Active Subscription?
        if (!$subscription || $subscription->status !== 'active' || ($subscription->ends_at && $subscription->ends_at->isPast())) {

            // Allow access to billing page only
            if ($request->routeIs('tenant.settings.billing*') || $request->routeIs('tenant.billing.callback')) {
                return $next($request);
            }

            // Redirect to billing with error
            session()->flash('flux.toast', 'Votre période d\'essai est terminée. Veuillez vous abonner pour continuer.');
            return redirect()->route('tenant.settings.billing');
        }

        return $next($request);
    }
}
