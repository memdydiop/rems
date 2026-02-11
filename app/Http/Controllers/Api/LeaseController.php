<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use Illuminate\Http\Request;

class LeaseController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Lease::with('renter', 'unit.property')->latest()->get()
        );
    }

    public function show(Lease $lease)
    {
        return response()->json(
            $lease->load('renter', 'unit', 'payments')
        );
    }
}
