# Personal Finance API

A Laravel 13 JSON API for viewing balances and transactions, managing budgets and savings pots, and moving money between the current balance and a pot. Local setup seeds a demo account, **John Doe** (`johndoe@example.com`), with sample finance data. The API uses Laravel Sanctum bearer tokens and has no login or registration endpoints.

## Local development

### Set up and start the API

For local development and tests, use PHP 8.4 or later, Composer, and the `pdo_sqlite` PHP extension.

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

A valid demo token returns `200 OK` with the user's details and balance fields. Each `demo:token` run displays the new token's plaintext only once. Keep it private and do not commit it to Git. Demo tokens have all the abilities listed below, including permission to modify budgets and pots.

## API endpoints

All `/api/` endpoints require a bearer token with the listed ability. Transactions, budgets, and pots are scoped to the authenticated user. Replace `{transaction}`, `{budget}`, or `{pot}` with the resource's UUID.

| Method | Path | Ability | Result |
| --- | --- | --- | --- |
| `GET` | `/api/user` | `read-user` | User details and `current`, `income`, and `expenses` balances |
| `GET` | `/api/transactions` | `manage-transactions` | Paginated transactions |
| `GET` | `/api/transactions/{transaction}` | `manage-transactions` | One transaction |
| `GET` | `/api/budgets` | `manage-budgets` | List budgets |
| `POST` | `/api/budgets` | `manage-budgets` | Create a budget (`201`) |
| `GET` | `/api/budgets/{budget}` | `manage-budgets` | One budget |
| `PUT`, `PATCH` | `/api/budgets/{budget}` | `manage-budgets` | Update budget fields (`200`) |
| `DELETE` | `/api/budgets/{budget}` | `manage-budgets` | Delete a budget (`204`) |
| `GET` | `/api/pots` | `manage-pots` | List savings pots |
| `POST` | `/api/pots` | `manage-pots` | Create an empty pot (`201`) |
| `GET` | `/api/pots/{pot}` | `manage-pots` | One savings pot |
| `PUT`, `PATCH` | `/api/pots/{pot}` | `manage-pots` | Update pot details (`200`) |
| `DELETE` | `/api/pots/{pot}` | `manage-pots` | Return any funds to the current balance and delete the pot (`204`) |
| `POST` | `/api/pots/{pot}/deposit` | `manage-pots` | Move funds from the current balance to the pot (`200`) |
| `POST` | `/api/pots/{pot}/withdraw` | `manage-pots` | Move funds from the pot to the current balance (`200`) |
| `GET` | `/up` | None | Public health check |

### List transactions

Transactions are read-only and returned 10 per page, newest transaction date first by default. The response puts records under `data`; follow `next_page_url` to keep the current filters and sort on the next page.

| Query parameter | Description |
| --- | --- |
| `search` | Match part of a transaction's name. |
| `category` | Filter by a category in the catalog, such as `Entertainment` or `Dining Out`. |
| `sort` | `latest`, `oldest`, `a to z`, `z to a`, `highest`, or `lowest`. The last two sort by signed amount. |
| `page` | Page number, starting at `1`. |

Example: `GET /api/transactions?search=Netflix&category=Entertainment&sort=oldest`.

### Write budgets and pots

Send a JSON body with `Content-Type: application/json`. Create requests require all fields shown below. Both `PUT` and `PATCH` currently apply partial updates: omitted fields are left unchanged.

| Operation | Example JSON body |
| --- | --- |
| Create a budget | `{"category":"Groceries","maximum":"150.00","theme":"#277C46"}` |
| Create a pot | `{"name":"Emergency fund","target":"500.00","theme":"#277C46"}` |
| Deposit or withdraw | `{"amount":"20.50"}` |

- Each user can have only one budget per category. A pot starts with a zero `total`; change it through deposits and withdrawals, not by updating pot details.
- Monetary inputs in these requests must be positive, with at most two decimal places. The API serializes monetary amounts as decimal strings such as `"20.50"`.
- Transfers and refunds update the pot and current balance in one database transaction. They do not change income or expenses. **Deposit and withdrawal requests are not idempotent**: retrying after a timeout can apply the transfer again.

### Error responses

- `401 Unauthorized`: missing or invalid token.
- `403 Forbidden`: the token lacks the required ability.
- `404 Not Found`: invalid UUID, missing resource, or a resource belonging to another user.
- `409 Conflict`: a funds movement cannot complete, for example because of insufficient funds or a missing balance.
- `422 Unprocessable Content`: invalid query parameters or request body. Field errors are under `errors`.

## Run the tests

```bash
./vendor/bin/pest
```

The feature tests use an in-memory SQLite database and create their own users and tokens. They do not need the demo seed data or a running API server.
