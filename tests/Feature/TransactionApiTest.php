<?php

use App\Models\Category;
use App\Models\Transaction;
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
    $token = $user->createToken('test', ['manage-transactions'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/transactions')
        ->assertOk()
        ->assertJsonIsArray('data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['avatar', 'name', 'category', 'date', 'amount', 'recurring'],
            ],
        ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/transactions')
        ->assertJsonPath('data.0.recurring', fn (mixed $value): bool => is_bool($value));

});

test('orders transactions by the requested sort', function (array $filters, array $expectedNames) {
    $user = User::factory()->create();
    createTransactionForUser($user, [
        'id' => 'ffffffff-ffff-4fff-bfff-fffffffffff1',
        'name' => 'Zulu',
        'date' => '2024-07-01 00:00:00',
        'amount' => '-15.00',
    ]);
    createTransactionForUser($user, [
        'id' => '22222222-2222-4222-8222-222222222222',
        'name' => 'Alpha',
        'date' => '2024-07-02 00:00:00',
        'amount' => '-5.00',
    ]);
    createTransactionForUser($user, [
        'id' => '00000000-0000-4000-8000-000000000003',
        'name' => 'Bravo',
        'date' => '2024-07-03 00:00:00',
        'amount' => '-25.00',
    ]);
    $token = $user->createToken('test', ['manage-transactions'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/transactions?'.http_build_query($filters));

    $response->assertOk()->assertJsonPath('data.*.name', $expectedNames);
})->with([
    'defaults to latest transaction date' => [[], ['Bravo', 'Alpha', 'Zulu']],
    'normalizes a mixed-case latest sort' => [['sort' => ' LaTeSt '], ['Bravo', 'Alpha', 'Zulu']],
    'oldest transaction date first' => [['sort' => 'oldest'], ['Zulu', 'Alpha', 'Bravo']],
    'name ascending' => [['sort' => 'a to z'], ['Alpha', 'Bravo', 'Zulu']],
    'name descending' => [['sort' => 'z to a'], ['Zulu', 'Bravo', 'Alpha']],
    'highest amount first' => [['sort' => 'highest'], ['Alpha', 'Zulu', 'Bravo']],
    'lowest amount first' => [['sort' => 'lowest'], ['Bravo', 'Zulu', 'Alpha']],
]);

test('retains the search, normalized category, and sort when following the next page', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Category::query()->forceCreate(['name' => 'Entertainment']);

    foreach (range(1, 11) as $day) {
        createTransactionForUser($user, [
            'name' => "Netflix {$day}",
            'date' => sprintf('2024-07-%02d 00:00:00', $day),
        ]);
    }

    createTransactionForUser($user, ['name' => 'Cinema']);
    createTransactionForUser($user, ['name' => 'Netflix gift card', 'category' => 'Shopping']);
    createTransactionForUser($otherUser, ['name' => 'Netflix family']);
    $token = $user->createToken('test', ['manage-transactions'])->plainTextToken;
    $client = $this->withHeader('Authorization', "Bearer {$token}");

    $firstPage = $client->getJson('/api/transactions?search=Netflix&category=eNtErTaInMeNt&sort=oldest');

    $firstPage->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('data.0.name', 'Netflix 1')
        ->assertJsonPath('total', 11);

    $client->getJson($firstPage->json('next_page_url'))
        ->assertOk()
        ->assertJsonPath('current_page', 2)
        ->assertJsonPath('data.*.name', ['Netflix 11']);
});

test('matches the search string zero instead of ignoring it', function () {
    $user = User::factory()->create();
    createTransactionForUser($user, ['name' => 'Route 10']);
    createTransactionForUser($user, ['name' => 'Cinema']);
    $token = $user->createToken('test', ['manage-transactions'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/transactions?search=0');

    $response->assertOk()->assertJsonPath('data.*.name', ['Route 10']);
});

test('returns an empty page when a valid category has no matching transactions', function () {
    $user = User::factory()->create();
    Category::query()->forceCreate(['name' => 'Dining Out']);
    createTransactionForUser($user);
    $token = $user->createToken('test', ['manage-transactions'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/transactions?category=Dining+Out');

    $response->assertOk()->assertJsonPath('data', [])->assertJsonPath('total', 0);
});

test('returns 422 for an invalid transaction list filter', function (array $filters, string $field, string $message) {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['manage-transactions'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/transactions?'.http_build_query($filters));

    $response->assertUnprocessable()->assertJsonPath("errors.{$field}.0", $message);
})->with([
    'unknown category' => [['category' => 'Unknown'], 'category', 'The selected category is invalid.'],
    'unsupported sort' => [['sort' => 'random'], 'sort', 'The selected sort is invalid.'],
    'array category' => [['category' => ['Entertainment']], 'category', 'The category field must be a string.'],
    'array sort' => [['sort' => ['latest']], 'sort', 'The sort field must be a string.'],
    'array search' => [['search' => ['Netflix']], 'search', 'The search field must be a string.'],
]);

test('returns the requested transaction when it belongs to the authenticated user', function () {
    $user = User::factory()->create();
    $transaction = createTransactionForUser($user, ['amount' => '-15.49', 'recurring' => true]);
    $token = $user->createToken('test', ['manage-transactions'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/transactions/{$transaction->id}");

    $response->assertOk()
        ->assertJsonPath('id', $transaction->id)
        ->assertJsonPath('amount', '-15.49')
        ->assertJsonPath('recurring', true);
});

test('returns 404 when the transaction belongs to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $transaction = createTransactionForUser($otherUser);
    $token = $user->createToken('test', ['manage-transactions'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/transactions/{$transaction->id}");

    $response->assertNotFound();
});

/**
 * @param  array<string, mixed>  $attributes
 */
function createTransactionForUser(User $user, array $attributes = []): Transaction
{
    return $user->transactions()->forceCreate([
        'avatar' => '/avatars/netflix.png',
        'name' => 'Netflix',
        'category' => 'Entertainment',
        'date' => '2024-07-02 00:00:00',
        'amount' => '-15.49',
        'recurring' => false,
        ...$attributes,
    ]);
}
