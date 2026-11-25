<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePotRequest;
use Brick\Math\BigDecimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $request->user()
            ->pots()
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SavePotRequest $request)
    {
        $validated = $request->validated();

        $pot = $request->user()->pots()->create($validated);

        return response()->json($pot, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $pot = $request->user()->pots()->findOrFail($id);

        return response()->json($pot);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SavePotRequest $request, string $id)
    {
        $validated = $request->validated();

        $pot = $request->user()->pots()->findOrFail($id);

        if (! $pot->update($validated)) {
            throw new RuntimeException('Could not update the pot.');
        }

        return response()->json($pot);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        DB::transaction(function () use ($user, $id): void {
            $pot = $user->pots()->lockForUpdate()->findOrFail($id);
            $total = BigDecimal::of($pot->total);

            if (! $total->isZero()) {
                $balance = $user->balance()->lockForUpdate()->first();

                abort_if($balance === null, 409, 'No current balance to return the pot funds to.');

                $newCurrent = BigDecimal::of($balance->current)->plus($total);

                abort_if($newCurrent->isGreaterThan('999999.99'), 409, 'Returning the pot funds would exceed the balance limit.');

                $balance->current = (string) $newCurrent;

                if (! $balance->save()) {
                    throw new RuntimeException('Could not save the updated balance.');
                }
            }

            if (! $pot->delete()) {
                throw new RuntimeException('Could not delete the pot.');
            }
        });

        return response()->noContent();
    }
}
