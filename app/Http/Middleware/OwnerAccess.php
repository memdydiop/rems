<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Owner;

class OwnerAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Check if user is linked to an Owner profile using the email
        $isOwner = Owner::where('email', $user->email)->exists() || Owner::where('user_id', $user->id)->exists();

        if (!$isOwner) {
            abort(403, 'Accès réservé aux propriétaires.');
        }

        // Share owner data with views
        $owner = Owner::where('user_id', $user->id)->first() ?? Owner::where('email', $user->email)->first();
        if ($owner) {
            view()->share('currentOwner', $owner);
        }

        return $next($request);
    }
}
