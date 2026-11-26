<?php

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
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
    $token = $user->createToken('test', ['manage-budgets'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/budgets')
        ->assertOk()
        ->assertJsonIsArray()
        ->assertJsonStructure(['*' => ['id', 'category', 'maximum', 'theme']]);
});

test('creates a budget when only another user has a budget for the category', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherUser->budgets()->create(['category' => 'Entertainment', 'maximum' => 50, 'theme' => '#277C78']);
    $token = $user->createToken('test', ['manage-budgets'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/budgets', ['category' => 'Entertainment', 'maximum' => 75.5, 'theme' => '#277C46']);

    $response->assertCreated()
        ->assertJsonPath('category', 'Entertainment')
        ->assertJsonPath('maximum', '75.50')
        ->assertJsonPath('theme', '#277C46');
    $this->assertDatabaseHas('budgets', [
        'id' => $response->json('id'),
        'user_id' => $user->id,
        'category' => 'Entertainment',
        'maximum' => '75.50',
        'theme' => '#277C46',
    ]);
});

test('returns 422 when creating a second budget for the same user and category', function () {
    $user = User::factory()->create();
    $budget = $user->budgets()->create(['category' => 'Entertainment', 'maximum' => 50, 'theme' => '#277C78']);
    $token = $user->createToken('test', ['manage-budgets'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/budgets', ['category' => 'Entertainment', 'maximum' => 75, 'theme' => '#277C46']);

    $response->assertUnprocessable()
        ->assertJsonPath('errors.category.0', 'You already have a category for this budget.');
    $this->assertDatabaseCount('budgets', 1);
    $this->assertDatabaseHas('budgets', ['id' => $budget->id, 'maximum' => 50]);
});

test('returns the requested budget when it belongs to the authenticated user', function () {
    $user = User::factory()->create();
    $budget = $user->budgets()->create(['category' => 'Entertainment', 'maximum' => 50, 'theme' => '#277C78']);
    $token = $user->createToken('test', ['manage-budgets'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/budgets/{$budget->id}");

    $response->assertOk()->assertExactJson([
        'id' => $budget->id,
        'category' => 'Entertainment',
        'maximum' => '50.00',
        'theme' => '#277C78',
    ]);
});

test('updates the maximum without rejecting the budget’s existing category', function () {
    $user = User::factory()->create();
    $budget = $user->budgets()->create(['category' => 'Entertainment', 'maximum' => 50, 'theme' => '#277C78']);
    $token = $user->createToken('test', ['manage-budgets'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/budgets/{$budget->id}", ['category' => 'Entertainment', 'maximum' => 75]);

    $response->assertOk()
        ->assertJsonPath('category', 'Entertainment')
        ->assertJsonPath('maximum', '75.00')
        ->assertJsonPath('theme', '#277C78');
    $this->assertDatabaseHas('budgets', [
        'id' => $budget->id,
        'category' => 'Entertainment',
        'maximum' => 75,
        'theme' => '#277C78',
    ]);
});

test('returns 422 when changing to a category already used by another budget of the same user', function () {
    $user = User::factory()->create();
    $budget = $user->budgets()->create(['category' => 'Entertainment', 'maximum' => 50, 'theme' => '#277C78']);
    $user->budgets()->create(['category' => 'Groceries', 'maximum' => 60, 'theme' => '#277C46']);
    $token = $user->createToken('test', ['manage-budgets'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/budgets/{$budget->id}", ['category' => 'Groceries', 'maximum' => 75]);

    $response->assertUnprocessable()
        ->assertJsonPath('errors.category.0', 'You already have a category for this budget.');
    $this->assertDatabaseHas('budgets', ['id' => $budget->id, 'category' => 'Entertainment', 'maximum' => 50]);
});

test('returns 204 and removes the budget when it belongs to the authenticated user', function () {
    $user = User::factory()->create();
    $budget = $user->budgets()->create(['category' => 'Entertainment', 'maximum' => 50, 'theme' => '#277C78']);
    $token = $user->createToken('test', ['manage-budgets'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/budgets/{$budget->id}");

    $response->assertNoContent();
    $this->assertModelMissing($budget);
});

test('returns 404 and leaves another user’s budget unchanged', function (string $method, array $payload) {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $budget = $otherUser->budgets()->create(['category' => 'Entertainment', 'maximum' => 50, 'theme' => '#277C78']);
    $token = $user->createToken('test', ['manage-budgets'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->json($method, "/api/budgets/{$budget->id}", $payload);

    $response->assertNotFound();
    $this->assertDatabaseHas('budgets', ['id' => $budget->id, 'user_id' => $otherUser->id, 'maximum' => 50]);
})->with([
    'show' => ['GET', []],
    'update' => ['PATCH', ['maximum' => 75]],
    'delete' => ['DELETE', []],
]);

test('rejects a duplicate user and category at the database level without HTTP validation', function () {
    $user = User::factory()->create();
    $user->budgets()->create(['category' => 'Entertainment', 'maximum' => 50, 'theme' => '#277C78']);

    expect(fn () => $user->budgets()->create(['category' => 'Entertainment', 'maximum' => 75, 'theme' => '#277C46']))
        ->toThrow(UniqueConstraintViolationException::class);
});
