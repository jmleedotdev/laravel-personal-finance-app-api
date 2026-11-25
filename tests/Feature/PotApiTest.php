<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the pots endpoint returns 401 json without a token or accept header', function () {
    $this->get('/api/pots')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthenticated.');
});

test('the pots endpoint returns pots fields', function () {
    $user = User::factory()->create();
    $user->pots()->forceCreate([
        'name' => 'Savings',
        'target'=> 2000,
        'total' => 159,
        'theme' => '#277C78',
    ]);
    $token = $user->createToken('test', ['demo:read'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/pots')
        ->assertOk()
        ->assertJsonIsArray()
        ->assertJsonStructure(['*' => ['id', 'name', 'target', 'total', 'theme']]);
});
