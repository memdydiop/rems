<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        // Allow filtering by property_id if provided
        $query = Unit::query()->with('property', 'activeLease');

        if ($request->has('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        return response()->json($query->get());
    }

    public function show(Unit $unit)
    {
        return response()->json($unit->load('property', 'activeLease', 'leases'));
    }
}
