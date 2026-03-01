<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use Illuminate\Http\Request;

class LeaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Lease::with('unit', 'renter')->latest();

        if ($request->has('renter_id')) {
            $query->where('renter_id', $request->renter_id);
        }

        if ($request->has('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        return response()->json($query->paginate(25));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit_id' => 'required|uuid|exists:units,id',
            'renter_id' => 'required|uuid|exists:renters,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'rent_amount' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'status' => 'sometimes|string',
        ]);

        $lease = Lease::create($validated);

        return response()->json($lease->load('unit', 'renter'), 201);
    }

    public function show(Lease $lease)
    {
        return response()->json(
            $lease->load('unit', 'renter', 'payments')
        );
    }

    public function update(Request $request, Lease $lease)
    {
        $validated = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'rent_amount' => 'sometimes|numeric|min:0',
            'deposit_amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string',
        ]);

        $lease->update($validated);

        return response()->json($lease);
    }

    public function destroy(Lease $lease)
    {
        $lease->delete();

        return response()->json(null, 204);
    }
}
