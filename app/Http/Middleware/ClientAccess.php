<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientAccess
{
    /**
     * Handle an incoming request.
     * Ensures the authenticated user has an associated client profile.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has a client profile
        $client = Client::where('user_id', $user->id)->first();

        if (!$client) {
            abort(403, 'Accès réservé aux locataires/clients.');
        }

        // Share client data with views
        view()->share('currentClient', $client);

        return $next($request);
    }
}
