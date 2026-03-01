<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Property::latest()->withCount('units')->paginate(25)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'type' => 'required|string',
            'status' => 'sometimes|string',
            'owner_id' => 'nullable|uuid|exists:owners,id',
        ]);

        $property = Property::create($validated);

        return response()->json($property, 201);
    }

    public function show(Property $property)
    {
        return response()->json(
            $property->load('units', 'owner')
        );
    }

    public function update(Request $request, Property $property)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:500',
            'type' => 'sometimes|string',
            'status' => 'sometimes|string',
            'owner_id' => 'nullable|uuid|exists:owners,id',
        ]);

        $property->update($validated);

        return response()->json($property);
    }

    public function destroy(Property $property)
    {
        $property->delete();

        return response()->json(null, 204);
    }
}
