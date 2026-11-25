<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the user endpoint returns 401 json without a token or accept header', function () {
    $this->get('/api/user')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthenticated.');
});

test('the user endpoint returns the token owner and balance fields', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['read-user'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonMissingPath('password')
        ->assertJsonStructure(['id', 'current', 'income', 'expenses']);
});
