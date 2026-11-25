<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the budgets endpoint returns 401 json without a token or accept header', function () {
    $this->get('/api/budgets')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthenticated.');
});

test('the budgets endpoint returns budgets fields', function () {
    $user = User::factory()->create();
    $user->budgets()->forceCreate([
        'category' => 'Entertainment',
        'maximum' => 50,
        'theme' => '#277C78',
    ]);
    $token = $user->createToken('test', ['demo:read'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/budgets')
        ->assertOk()
        ->assertJsonIsArray()
        ->assertJsonStructure(['*' => ['id', 'category', 'maximum', 'theme']]);
});
