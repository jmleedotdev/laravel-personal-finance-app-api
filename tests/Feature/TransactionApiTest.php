<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the transactions endpoint returns 401 json without a token or accept header', function () {
    $this->get('/api/transactions')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthenticated.');
});

test('the transactions endpoint returns transactions fields', function () {
    $user = User::factory()->create();
    $user->transactions()->forceCreate([
        'avatar' => '/avatars/netflix.png',
        'name' => 'Netflix',
        'category' => 'Entertainment',
        'date' => '2024-07-02 19:50:05',
        'amount' => -15.49,
        'recurring' => true,
    ]);
    $token = $user->createToken('test', ['demo:read'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/transactions')
        ->assertOk()
        ->assertJsonIsArray('data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['avatar', 'name', 'category', 'date', 'amount', 'recurring']
            ]
        ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/transactions')
        ->assertJsonPath('data.0.recurring', fn (mixed $value): bool => is_bool($value));

});
