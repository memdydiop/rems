<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RentPayment;
use Illuminate\Http\Request;

class RentPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = RentPayment::with('lease.renter', 'lease.unit')->latest();

        if ($request->has('lease_id')) {
            $query->where('lease_id', $request->lease_id);
        }

        return response()->json($query->paginate(25));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lease_id' => 'required|uuid|exists:leases,id',
            'amount' => 'required|numeric|min:0',
            'paid_at' => 'required|date',
            'method' => 'nullable|string|max:50',
            'status' => 'sometimes|string',
            'notes' => 'nullable|string',
        ]);

        $payment = RentPayment::create($validated);

        return response()->json($payment->load('lease'), 201);
    }

    public function show(RentPayment $rentPayment)
    {
        return response()->json(
            $rentPayment->load('lease.renter', 'lease.unit')
        );
    }

    public function update(Request $request, RentPayment $rentPayment)
    {
        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0',
            'paid_at' => 'sometimes|date',
            'method' => 'sometimes|string|max:50',
            'status' => 'sometimes|string',
            'notes' => 'nullable|string',
        ]);

        $rentPayment->update($validated);

        return response()->json($rentPayment);
    }

    public function destroy(RentPayment $rentPayment)
    {
        $rentPayment->delete();

        return response()->json(null, 204);
    }
}
