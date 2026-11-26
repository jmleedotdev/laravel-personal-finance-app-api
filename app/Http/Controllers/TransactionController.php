<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListTransactionsRequest;
use Illuminate\Http\Request;
use LogicException;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private function getSorting(string $sort) {
        $sortMap = [
            'latest' => ['date', 'desc'],
            'oldest' => ['date', 'asc'],
            'a to z' => ['name', 'asc'],
            'z to a' => ['name','desc'],
            'highest' => ['amount', 'desc'],
            'lowest' => ['amount', 'asc']
        ];

        if (! isset($sortMap[$sort])) {
            throw new LogicException("No sort mapping for [$sort].");
        }

        return $sortMap[$sort];
    }

    public function index(ListTransactionsRequest $request)
    {
        $filters = $request->validated();
        $search = $filters['search'] ?? null;
        $sort = $filters['sort'] ?? null;
        $category = $filters['category'] ?? null;
        $transactions = $request->user()->transactions();

        if ($search !== null) {
            $transactions->where('name', 'like', "%{$search}%");
        }

        if ($category !== null) {
            $transactions->where('category', $category);
        }

        if ($sort) {
            [$column, $ordering] = $this->getSorting($sort);
            $transactions->orderBy($column, $ordering)->orderByDesc('id');
        } else {
            $transactions->orderByDesc('date')->orderByDesc('id');
        }

        return $transactions->paginate(10)->withQueryString();
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $transaction = $request->user()->transactions()->findOrFail($id);

        return response()->json($transaction);
    }
}
