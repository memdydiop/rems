<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;

class MaintenanceRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = MaintenanceRequest::with('property', 'unit', 'user')->latest();

        if ($request->has('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        return response()->json($query->paginate(25));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|string',
            'property_id' => 'required|uuid|exists:properties,id',
            'unit_id' => 'nullable|uuid|exists:units,id',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'open';

        $maintenance = MaintenanceRequest::create($validated);

        return response()->json($maintenance->load('property', 'unit'), 201);
    }

    public function show(MaintenanceRequest $maintenanceRequest)
    {
        return response()->json(
            $maintenanceRequest->load('property', 'unit', 'user')
        );
    }

    public function update(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|string',
            'priority' => 'sometimes|string',
        ]);

        $maintenanceRequest->update($validated);

        return response()->json($maintenanceRequest);
    }

    public function destroy(MaintenanceRequest $maintenanceRequest)
    {
        $maintenanceRequest->delete();

        return response()->json(null, 204);
    }
}
