<?php

namespace App\Http\Controllers;

use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PotController extends Controller
{
    private function verifyUuid(string $id): void {
        abort_unless(Str::isUuid($id), 404, 'Pot not found.');
    }

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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'target' => ['required', 'numeric', 'decimal:0,2', 'max:999999.99', 'gt:0'],
            'theme' => ['required', 'string', 'max:7', 'regex:/\A#[0-9a-fA-F]{6}\z/'],
        ], [
            'name.required' => 'The pot name is required.',
            'name.string' => 'The pot name must be a string.',
            'name.max' => 'The pot name must not be greater than 255 characters.',
            'target.required' => 'The target is required.',
            'target.numeric' => 'The target must be a number.',
            'target.decimal' => 'The target must not be greater than 2 decimals.',
            'theme.required' => 'The theme hex code is required.',
            'theme.string' => 'The theme hex code must be a string.',
            'theme.max' => 'The theme hex code must not be greater than 7 characters.',
            'theme.regex' => 'The theme must be a color hex code.',
        ]);

        $pot = $request->user()->pots()->create($validated);

        return response()->json($pot, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $this->verifyUuid($id);

        $pot = $request->user()->pots()->findOrFail($id);

        return response()->json($pot);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $this->verifyUuid($id);

        $user = $request->user();

        DB::transaction(function () use ($user, $id): void {
            $pot = $user->pots()->lockForUpdate()->findOrFail($id);
            $total = BigDecimal::of($pot->total);

            if (!$total->isZero()) {
                $balance = $user->balance()->lockForUpdate()->first();

                abort_if($balance === null, 409, 'No current balance to return the pot funds to.');

                $newCurrent = BigDecimal::of($balance->current)->plus($total);

                abort_if($newCurrent->isGreaterThan('999999.99'), 409, 'Returning the pot funds would exceed the balance limit.');

                $balance->current = (string) $newCurrent;

                if (!$balance->save()) {
                    throw new RuntimeException('Could not save the updated balance.');
                }
            }

            if (!$pot->delete()) {
                throw new RuntimeException('Could not delete the pot.');
            }
        });

        return response()->noContent();
    }
}
