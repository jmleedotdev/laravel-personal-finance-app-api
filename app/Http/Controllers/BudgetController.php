<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveBudgetRequest;
use Illuminate\Http\Request;
use RuntimeException;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $request->user()
            ->budgets()
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaveBudgetRequest $request)
    {
        $budget = $request->user()->budgets()->create($request->validated());

        return response()->json($budget, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $budget = $request->user()->budgets()->findOrFail($id);

        return response()->json($budget);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SaveBudgetRequest $request, string $id)
    {
        $validated = $request->validated();
        $budget = $request->user()->budgets()->findOrFail($id);

        if (! $budget->update($validated)) {
            throw new RuntimeException('Could not update the budget.');
        }

        return response()->json($budget);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $budget = $request->user()->budgets()->findOrFail($id);

        if (! $budget->delete()) {
            throw new RuntimeException('Could not delete the budget.');
        }

        return response()->noContent();
    }
}
