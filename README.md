# Personal Finance API

A Laravel 13 JSON API that serves a user's balance, transactions, budgets, and savings pots. Local setup seeds a demo account, **John Doe** (`johndoe@example.com`), with sample finance data. The API uses Laravel Sanctum bearer tokens and does not require a login or registration endpoint.

## Local development

### Set up and start the API

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

The example environment already selects SQLite. Leave `DB_DATABASE` unset to use the project's `database/database.sqlite` file.

### Make an authenticated request

With the server running, open another terminal in the project directory and issue a token for the seeded demo user:

```bash
php artisan demo:token local-dev
```

Copy the full token, including the part before `|`. Run the block below, then paste the token and press Enter when `read` waits for input. The token won't be echoed to the terminal.

```bash
read -rs TOKEN
curl -i http://localhost:8000/api/user \
  -H 'Accept: application/json' \
  -H "Authorization: Bearer $TOKEN"
unset TOKEN
```

A valid token returns `200 OK` with the demo user's details and balance fields. A missing or invalid token returns `401 Unauthorized`. Each `demo:token` run creates a new token and displays its plaintext only once. Keep it private and do not commit it to Git.

## Implemented endpoints

| Method | Path                | Response                                                                |
| ------ | ------------------- | ----------------------------------------------------------------------- |
| `GET`  | `/api/user`         | Authenticated user, plus `current`, `income`, and `expenses`            |
| `GET`  | `/api/transactions` | Newest transactions first, 10 per page; use `?page=2` for the next page |
| `GET`  | `/api/budgets`      | The authenticated user's budgets                                        |
| `GET`  | `/api/pots`         | The authenticated user's savings pots                                   |
| `GET`  | `/up`               | Public health check; no token required                                  |

The API endpoints under `/api/` require a Sanctum bearer token.

## Run the tests

```bash
./vendor/bin/pest
```

The feature tests use an in-memory SQLite database and create their own users and tokens. They do not need the demo seed data or a running API server.
