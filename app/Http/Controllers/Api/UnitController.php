<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::with('property')->latest();

        if ($request->has('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        return response()->json($query->paginate(25));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|uuid|exists:properties,id',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string',
            'rent_amount' => 'required|numeric|min:0',
            'status' => 'sometimes|string',
        ]);

        $unit = Unit::create($validated);

        return response()->json($unit->load('property'), 201);
    }

    public function show(Unit $unit)
    {
        return response()->json(
            $unit->load('property', 'leases')
        );
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string',
            'rent_amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string',
        ]);

        $unit->update($validated);

        return response()->json($unit);
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();

        return response()->json(null, 204);
    }
}
