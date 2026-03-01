<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('property', 'vendor')->latest();

        if ($request->has('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        return response()->json($query->paginate(25));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'nullable|uuid|exists:properties,id',
            'vendor_id' => 'nullable|uuid|exists:vendors,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'category' => 'sometimes|string|max:100',
            'receipt_path' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $expense = Expense::create($validated);

        return response()->json($expense->load('property', 'vendor'), 201);
    }

    public function show(Expense $expense)
    {
        return response()->json(
            $expense->load('property', 'vendor')
        );
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'property_id' => 'nullable|uuid|exists:properties,id',
            'vendor_id' => 'nullable|uuid|exists:vendors,id',
            'description' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
            'date' => 'sometimes|date',
            'category' => 'sometimes|string|max:100',
            'receipt_path' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $expense->update($validated);

        return response()->json($expense);
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return response()->json(null, 204);
    }
}
