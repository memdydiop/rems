<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Owner::with('properties')->latest()->paginate(25)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'account_details' => 'nullable|string',
            'status' => 'sometimes|string',
        ]);

        $owner = Owner::create($validated);

        return response()->json($owner, 201);
    }

    public function show(Owner $owner)
    {
        return response()->json(
            $owner->load('properties')
        );
    }

    public function update(Request $request, Owner $owner)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'account_details' => 'nullable|string',
            'status' => 'sometimes|string',
        ]);

        $owner->update($validated);

        return response()->json($owner);
    }

    public function destroy(Owner $owner)
    {
        $owner->delete();

        return response()->json(null, 204);
    }
}
