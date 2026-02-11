<?php

namespace App\Http\Middleware;

use App\Models\Renter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RenterAccess
{
    /**
     * Handle an incoming request.
     * Ensures the authenticated user has an associated renter profile.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has a renter profile
        $renter = Renter::where('user_id', $user->id)->first();

        if (!$renter) {
            abort(403, 'Accès réservé aux locataires.');
        }

        // Share renter data with views
        view()->share('currentRenter', $renter);

        return $next($request);
    }
}
