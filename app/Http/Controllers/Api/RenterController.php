<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Renter;
use Illuminate\Http\Request;

class RenterController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Renter::with('leases.unit.property')->latest()->get()
        );
    }

    public function show(Renter $renter)
    {
        return response()->json(
            $renter->load('leases.unit', 'leases.payments')
        );
    }
}
