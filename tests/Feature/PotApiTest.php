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
        'target' => 2000,
        'total' => 159,
        'theme' => '#277C78',
    ]);
    $token = $user->createToken('test', ['manage-pots'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/pots')
        ->assertOk()
        ->assertJsonIsArray()
        ->assertJsonStructure(['*' => ['id', 'name', 'target', 'total', 'theme']]);
});

test('creates a pot owned by the authenticated user with a zero starting total', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = $user->createToken('test', ['manage-pots'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/pots', [
            'name' => 'Test Pot',
            'target' => 20.50,
            'theme' => '#277C46',
            'user_id' => $otherUser->id,
            'total' => 15,
        ]);

    $response->assertCreated()
        ->assertJsonStructure(['id', 'name', 'target', 'total', 'theme'])
        ->assertJsonPath('name', 'Test Pot')
        ->assertJsonPath('target', 20.5)
        ->assertJsonPath('total', 0)
        ->assertJsonPath('theme', '#277C46');

    $this->assertDatabaseHas('pots', [
        'id' => $response->json('id'),
        'user_id' => $user->id,
        'name' => 'Test Pot',
        'target' => 20.5,
        'total' => 0,
        'theme' => '#277C46',
    ]);
});

test('returns 401 without a token when creating a pot', function () {
    $response = $this->postJson('/api/pots', [
        'name' => 'Test Pot',
        'target' => 20.50,
        'theme' => '#277C46',
    ]);

    $response->assertUnauthorized();
    $this->assertDatabaseCount('pots', 0);
});

test('returns 403 when the token lacks the manage-pots ability', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['read-user'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/pots', [
            'name' => 'Test Pot',
            'target' => 20.50,
            'theme' => '#277C46',
        ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('pots', 0);
});

test('returns 422 when required pot fields are missing', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['manage-pots'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/pots');

    $response->assertUnprocessable()
        ->assertJsonPath('errors.name.0', 'The pot name is required.')
        ->assertJsonPath('errors.target.0', 'The target is required.')
        ->assertJsonPath('errors.theme.0', 'The theme hex code is required.');

    $this->assertDatabaseCount('pots', 0);
});

test('returns 422 when a pot field is invalid', function (string $field, mixed $value, string $message) {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['manage-pots'])->plainTextToken;
    $payload = [
        'name' => 'Test Pot',
        'target' => 20.50,
        'theme' => '#277C46',
    ];
    $payload[$field] = $value;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/pots', $payload);

    $response->assertUnprocessable()
        ->assertJsonPath("errors.{$field}.0", $message);

    $this->assertDatabaseCount('pots', 0);
})->with([
    'name is too long' => ['name', str_repeat('a', 256), 'The pot name must not be greater than 255 characters.'],
    'target has too many decimal places' => ['target', 20.505, 'The target must not be greater than 2 decimals.'],
    'target exceeds the maximum' => ['target', 1000000, 'The target field must not be greater than 999999.99.'],
    'target is zero' => ['target', 0, 'The target field must be greater than 0.'],
    'theme is not a hex color' => ['theme', '#ZZZZZZ', 'The theme must be a color hex code.'],
]);

test('returns the requested pot when it belongs to the authenticated user', function () {
    $user = User::factory()->create();
    $pot = $user->pots()->forceCreate([
        'name' => 'Savings',
        'target' => 2000,
        'total' => 159,
        'theme' => '#277C78',
    ]);
    $token = $user->createToken('test', ['manage-pots'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/pots/{$pot->id}");

    $response->assertOk()->assertExactJson([
        'id' => $pot->id,
        'name' => 'Savings',
        'target' => 2000,
        'total' => 159,
        'theme' => '#277C78',
    ]);
});

test('returns 404 when showing a pot owned by another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $pot = $otherUser->pots()->create([
        'name' => 'Savings',
        'target' => 2000,
        'theme' => '#277C78',
    ]);
    $token = $user->createToken('test', ['manage-pots'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/pots/{$pot->id}");

    $response->assertNotFound();
});

test('returns 404 when showing a pot with an unknown UUID', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['manage-pots'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/pots/52cc790f-a65b-464b-abcc-0b755cf4c8e8');

    $response->assertNotFound();
});

test('returns 404 with a pot not found message when the ID is not a UUID', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['manage-pots'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/pots/not-a-uuid');

    $response->assertNotFound()->assertJsonPath('message', 'Pot not found.');
});

test('returns 401 without a token when showing a pot', function () {
    $response = $this->getJson('/api/pots/52cc790f-a65b-464b-abcc-0b755cf4c8e8');

    $response->assertUnauthorized();
});

test('returns 403 when showing a pot without the manage-pots ability', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['read-user'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/pots/52cc790f-a65b-464b-abcc-0b755cf4c8e8');

    $response->assertForbidden();
});
