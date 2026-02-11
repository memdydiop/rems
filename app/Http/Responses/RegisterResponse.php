<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        if (in_array($request->getHost(), config('tenancy.central_domains'))) {
            return redirect()->route('central.dashboard');
        }

        return redirect()->intended(route('dashboard'));
    }
}
