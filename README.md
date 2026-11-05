# Bantay Presyo API

REST API for monitoring market prices of basic goods in the Philippines. Users report the prices they see at public markets, and the API compares them with the DTI suggested retail price (SRP) in effect.

![API docs](docs/screenshot-docs.png)

## Stack

- PHP 8.3, Laravel 12
- PostgreSQL 16
- Laravel Sanctum (token auth)
- Pest, Laravel Pint, PHPStan (Larastan)
- Scribe for API docs
- FrankenPHP (Caddy) in production

## Setup

### With Docker

```bash
docker compose up --build
```

This starts PostgreSQL and the app on <http://localhost:8000>. On first run it copies `.env.example` to `.env`, generates an app key, runs migrations and seeds sample data. Mail is written to `storage/logs/laravel.log`, so verification and password reset links can be copied from there.

### Without Docker

Requires PHP 8.3 with `pdo_pgsql`, Composer, and a PostgreSQL 16 server. You can start only the database with `docker compose up -d db`.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Environment variables

| Variable | Default | Notes |
| --- | --- | --- |
| `APP_KEY` | empty | Set by `php artisan key:generate` |
| `APP_URL` | `http://localhost:8000` | Used as the base URL in the docs |
| `DB_CONNECTION` | `pgsql` | |
| `DB_HOST` | `127.0.0.1` | `db` inside Docker Compose |
| `DB_PORT` | `5432` | |
| `DB_DATABASE` | `bantay_presyo` | |
| `DB_USERNAME` | `bantay` | |
| `DB_PASSWORD` | `secret` | |
| `FRONTEND_URL` | `http://localhost:3000` | Password reset links go to `FRONTEND_URL/reset-password?token=&email=` |
| `CORS_ALLOWED_ORIGINS` | `*` | Comma-separated origins allowed to call the API from a browser |
| `SANCTUM_EXPIRATION` | `43200` | Token lifetime in minutes (30 days) |
| `PRICE_FLAG_THRESHOLD_PERCENT` | `30` | Reports this far above SRP are flagged |
| `PRICE_REPORTS_PER_MINUTE` | `10` | Per-user limit on new reports |
| `PRICE_REPORTS_PER_DAY` | `200` | Per-user limit on new reports |
| `PRICE_ANALYTICS_CACHE_SECONDS` | `300` | Cache lifetime for trend and compare results |

See `.env.example` for the full list, and `.env.production.example` for production.

## Sample data

`php artisan db:seed` creates 13 commodities, 8 markets, two SRP bulletins per commodity and 30 days of price reports. The prices are made up for local testing and are not an official DTI bulletin. The seeder does nothing when `APP_ENV=production`.

| Email | Password | Role |
| --- | --- | --- |
| `admin@bantaypresyo.test` | `password` | admin |
| `reporter@bantaypresyo.test` | `password` | reporter |

## API

Base path: `/api/v1`. Docs are at <http://localhost:8000/docs>, with the OpenAPI spec at `/docs.openapi` and a Postman collection at `/docs.postman`.

| Method | Path | Auth |
| --- | --- | --- |
| `POST` | `/auth/register` | |
| `POST` | `/auth/login` | |
| `POST` | `/auth/logout` | token |
| `POST` | `/auth/forgot-password`, `/auth/reset-password` | |
| `GET` | `/auth/email/verify/{id}/{hash}` | signed link |
| `POST` | `/auth/email/verification-notification` | token |
| `GET` | `/commodities?category=&q=` | |
| `GET` | `/commodities/{id}` | |
| `GET` | `/commodities/{id}/trend?from=&to=&market_id=` | |
| `GET` | `/commodities/{id}/compare?days=&market_id=` | |
| `POST`, `PUT`, `DELETE` | `/commodities`, `/commodities/{id}` | admin |
| `GET` | `/markets?city=&region=`, `/markets/{id}` | |
| `POST`, `PUT`, `DELETE` | `/markets`, `/markets/{id}` | admin |
| `GET` | `/srps?commodity_id=` | |
| `POST`, `DELETE` | `/srps`, `/srps/{id}` | admin |
| `POST` | `/srps/import` | admin |
| `GET` | `/price-reports?commodity_id=&market_id=&from=&to=&flagged=` | |
| `POST` | `/price-reports` | verified email |
| `POST`, `DELETE` | `/price-reports/{id}/hide` | admin |

### Responses

Success:

```json
{ "data": { "id": 1, "name": "Well-milled rice", "category": "Rice", "unit": "kg" } }
```

Lists add `meta` and accept `?page=` and `?per_page=` (default 20, max 100):

```json
{ "data": [], "meta": { "page": 1, "per_page": 20, "total": 13 } }
```

Errors:

```json
{ "error": { "code": "validation_failed", "message": "The given data was invalid.", "details": { "price": ["The price field must be greater than 0."] } } }
```

Timestamps are ISO 8601 in UTC.

### Trend and SRP comparison

`/trend` returns the average, minimum and maximum reported price per day. It covers the last 30 days by default, and a range can be at most 366 days.

`/compare` averages the reports from the last `days` days (default 7, including today) and compares that average with the latest SRP whose `effective_date` is on or before today:

```
difference         = average_price - srp
percent_difference = (average_price - srp) / srp * 100
```

Both values are rounded to 2 decimals. `status` is `above_srp`, `below_srp` or `at_srp`. If there is no SRP or there are no reports, these fields are `null`.

### Accounts

Registering sends a verification email. Logging in works right away, but submitting price reports needs a verified email. Tokens expire after 30 days (`expires_at` in the login response), and resetting a password logs the user out everywhere.

There is no API for granting admin rights. Use the console:

```bash
php artisan users:admin someone@example.com
php artisan users:admin someone@example.com --revoke
```

Either way the user's tokens are revoked, so the change applies on their next login.

### Report review

Each report stores the SRP in effect at `reported_at` (`srp_price`). Reports more than 30% above it get `flagged: true`; list them with `GET /price-reports?flagged=1`. A flagged report still counts until an admin hides it with `POST /price-reports/{id}/hide` (optional `reason`). Hidden reports are left out of listings, trends and comparisons. Admins can see them with `?include_hidden=1` and restore them with `DELETE /price-reports/{id}/hide`.

Each user can submit 10 reports a minute and 200 a day. Login and registration are limited to 10 requests a minute per IP.

### SRP bulletins

Load a bulletin from CSV, either from the console or with `POST /srps/import` (multipart `file`, optional `effective_date`):

```bash
php artisan srps:import docs/srp-import-example.csv
php artisan srps:import bulletin.csv --effective-date=2026-11-01
```

The header needs `commodity` and `price`; `unit` and `effective_date` are optional. Commodities are matched by name, ignoring case, and by unit if given. Prices may include `₱` and thousands separators. An SRP that already exists for the same commodity and date is updated. If any row is invalid, nothing is imported and every bad row is reported. See [`docs/srp-import-example.csv`](docs/srp-import-example.csv).

### Example

```bash
TOKEN=$(curl -s -X POST localhost:8000/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"reporter@bantaypresyo.test","password":"password"}' | jq -r .data.token)

curl -s -X POST localhost:8000/api/v1/price-reports \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"commodity_id":1,"market_id":1,"price":48.50}'

curl -s localhost:8000/api/v1/commodities/1/compare
```

## Production

The `prod` stage of the Dockerfile runs the app on FrankenPHP with OPcache and without dev dependencies. Caddy gets a TLS certificate for `SERVER_NAME` automatically. It needs ports 80 and 443 open and a DNS record pointing at the server.

```bash
cp .env.production.example .env.production   # fill in APP_KEY, domain, DB and mail settings
docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --show
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php artisan users:admin you@example.com
```

On start the container caches config and routes and runs migrations. It never seeds data. The `scheduler` service runs `schedule:work`, which prunes expired tokens daily. Logs go to stderr (`docker compose logs`), and `GET /up` returns 200 only when the database is reachable, so it can be used for uptime checks.

Back up the database with `pg_dump`, for example from a daily cron job:

```bash
docker compose -f docker-compose.prod.yml exec -T db pg_dump -U bantay -Fc bantay_presyo > backup-$(date +%F).dump
# restore: docker compose -f docker-compose.prod.yml exec -T db pg_restore -U bantay -d bantay_presyo --clean < backup.dump
```

The docs under `/docs` are generated files kept in the repo. Run `php artisan scribe:generate` after changing endpoints.

## Tests

The tests run against PostgreSQL, using the `bantay_presyo_test` database. Docker Compose creates it when the database volume is first initialized.

```bash
docker compose up -d db
vendor/bin/pest
```

Lint and static analysis:

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

CI runs all three on every push and pull request, and also builds the production image.

## License

MIT
