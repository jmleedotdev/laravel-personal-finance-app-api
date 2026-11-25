<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
    public function show(string $id)
    {
        //
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
    public function destroy(string $id)
    {
        //
    }
}
