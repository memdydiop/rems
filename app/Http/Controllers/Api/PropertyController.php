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
            Property::latest()->withCount('units')->get()
        );
    }

    public function show(Property $property)
    {
        return response()->json(
            $property->load('units')
        );
    }
}
