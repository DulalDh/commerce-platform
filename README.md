# Commerce Platform — Multi-Tenant Marketplace API

A multi-tenant, multi-vertical marketplace API built with Laravel. A single
platform hosts many independent tenants (merchants/service providers), each
selling either **products** (product marketplace) or **services with
bookings** (service marketplace), on top of shared platform features:
catalog, inventory, orders, payments, promotions, shipping, subscriptions,
reviews, reports, notifications, and a lightweight CMS.

Tenant isolation is enforced in two layers:

- **Application layer** — every tenant-owned model is scoped by `tenant_id`
  via Eloquent global scopes and the `ResolveTenant` middleware, which
  resolves the current tenant from the authenticated user (or lets a
  `super-admin` impersonate a tenant via the `X-Tenant-ID` header).
- **Database layer** — Postgres **Row-Level Security (RLS)** is enabled and
  *forced* on all ~39 tenant-owned tables, so a bug in application code
  cannot leak cross-tenant data. The app connects as a dedicated
  non-superuser role (`marketplace_app`) because RLS is always bypassed by
  Postgres superusers.

## Tech stack

| Layer            | Technology |
|-------------------|------------|
| Language / Framework | PHP 8.2+, Laravel 12 |
| Database          | PostgreSQL 16 (with Row-Level Security) |
| Cache / Sessions / Queue | Redis 7 |
| Search            | Meilisearch |
| Object storage    | S3-compatible (MinIO locally) |
| Auth              | Laravel Sanctum (bearer tokens) + Laravel Socialite (OAuth) |
| Authorization     | spatie/laravel-permission (roles & permissions, tenant-scoped) |
| PDF generation    | barryvdh/laravel-dompdf |
| Testing           | Pest (+ pest-plugin-laravel) |
| Static analysis   | Larastan / PHPStan |
| Code style        | Laravel Pint |
| Containers        | Docker Compose (app, nginx, postgres, redis, meilisearch, minio) |

## Architecture

The app is organized as self-contained **modules** under `app/Modules/*`,
each with its own routes, controllers, models, requests, migrations,
providers, and (where applicable) policies:

```
Auth · Tenants · RBAC · Catalog · Inventory · Orders · Payments ·
Customers · Marketplace · Subscriptions · Shipping · Promotions ·
Services · Bookings · Reviews · Reports · Notifications · CMS
```

Each module registers its own `Routes/api.php` via its
`*ServiceProvider`; all API routes ultimately hang off `routes/api.php` /
`bootstrap/app.php`. Global middleware applied to every API request:
`ForceJsonResponse`, `ResolveTenant` (tenant scoping), `AuditLog`.

## Prerequisites

- PHP 8.2+ with the `pdo_pgsql` and `redis` extensions
- Composer
- Node.js + npm (for the Vite-built frontend assets, minimal in this API-first app)
- Docker + Docker Compose (recommended — provides Postgres, Redis, Meilisearch, MinIO)

## Getting started

### 1. Clone and install dependencies

```bash
git clone <repo-url>
cd commerce-platform
composer install
npm install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

The defaults in `.env.example` are already wired for the Docker Compose
stack below (Postgres on `5432`, Redis on `6379`, Meilisearch on `7700`,
MinIO/S3 on `9000`). Adjust `DB_*`, `REDIS_*`, `MEILISEARCH_*`, `AWS_*` if
you're pointing at your own infrastructure instead.

### 3. Start infrastructure (Docker)

```bash
docker compose up -d
```

This brings up:

- `postgres` — Postgres 16, database `marketplace`
- `redis` — cache/session/queue backend
- `meilisearch` — search engine (master key `masterKeyChangeMe` by default)
- `minio` / `minio-init` — S3-compatible object storage + bucket bootstrap
- `app` / `nginx` — the app served on `http://localhost:8080` (optional —
  you can also just run `php artisan serve` locally against these
  containers, see step 5)

`docker/postgres/init.sql` runs once on first boot and creates the
non-superuser `marketplace_app` role the application actually connects as
(required for Postgres RLS to be enforced), plus a separate
`marketplace_test` database for the test suite.

> Running without Docker? Point `.env` at your own Postgres/Redis/Meilisearch
> instances and replicate `docker/postgres/init.sql` manually — the app
> **must** connect as a non-superuser Postgres role for RLS to take effect.

### 4. Run database migrations

```bash
php artisan migrate
```

Migrations live both in `database/migrations/` (core/framework tables) and
inside each `app/Modules/*/Database/Migrations/` (module-owned tables).
Laravel auto-discovers module migration paths via each module's service
provider.

### 5. (Optional) Seed base data

```bash
php artisan db:seed
```

`database/seeders/DatabaseSeeder.php` creates a basic test user. To seed
the platform's RBAC roles/permissions (`super-admin` role with
`manage-roles`, `manage-permissions`, `manage-tenants` permissions), run:

```bash
php artisan db:seed --class="App\\Modules\\RBAC\\Database\\Seeders\\RolesAndPermissionsSeeder"
```

Note this seeder only *creates the role* — it does not assign it to any
user (see [Permissions & roles](#permissions--roles-rbac) below for how to
promote a user to `super-admin`).

### 6. Run the app

```bash
composer dev
```

This runs the PHP dev server, queue worker, log tailer (`pail`), and Vite
dev server concurrently. Or run pieces individually:

```bash
php artisan serve                              # HTTP server
php artisan queue:listen --tries=1 --timeout=0 # queue worker
npm run dev                                    # Vite (assets)
```

The API is now available at `http://127.0.0.1:8000/api` (or
`http://localhost:8080/api` if using the Dockerized nginx).

## Testing

```bash
composer test
# or directly:
./vendor/bin/pest
```

CI (`.github/workflows/*.yml`) runs against a dedicated `marketplace_test`
Postgres database with the same non-superuser role setup as local Docker,
plus:

```bash
./vendor/bin/pint --test                                   # code style check
./vendor/bin/phpstan analyse --no-progress --memory-limit=512M  # static analysis
php artisan migrate --pretend                               # migration dry-run
```

Run `./vendor/bin/pint` (no `--test`) to auto-fix style issues.

## Exploring / testing the API

- A ready-to-import Postman collection with example requests for every
  endpoint lives at `docs/postman/marketplace-api.postman_collection.json`
  (folders numbered by build phase: Tenants/Auth/RBAC → Catalog/Inventory/
  Orders/Payments/Customers → Super Admin/Marketplace/Subscriptions/
  Shipping/Promotions → Services/Bookings/Reviews → Search/Reports/
  Notifications/CMS).
- `docs/API_CHANGELOG.md` tracks the frozen v1.0.0 API contract and the
  rules for evolving it.
- `docs/load-test/golden-path.js` is a k6 load-testing script covering the
  core happy-path flow.

### Typical flow: register a tenant, log in, create a brand

```bash
# 1. Register a tenant — creates the Tenant, its owner User, and assigns
#    that user the tenant-scoped "tenant-owner" role, all in one transaction.
curl -X POST http://127.0.0.1:8000/api/tenants \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Acme Co",
    "type": "merchant",
    "subdomain": "acme",
    "owner_name": "Alice Owner",
    "owner_email": "owner@example.test",
    "owner_password": "secret123"
  }'

# 2. Log in as the owner to get a Sanctum bearer token
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "owner@example.test", "password": "secret123"}'

# 3. Create a brand under that tenant (requires an authenticated,
#    tenant-scoped user — any role, not just super-admin)
curl -X POST http://127.0.0.1:8000/api/brands \
  -H "Authorization: Bearer <token from step 2>" \
  -H "Content-Type: application/json" \
  -d '{"name": "Acme Brand", "slug": "acme-brand-1"}'
```

## Permissions & roles (RBAC)

Authorization uses `spatie/laravel-permission`, with roles scoped per
tenant via a custom `tenant_id` column (see `App\Modules\RBAC\Models\Role`).
Two roles matter most:

- **`tenant-owner`** — created automatically per-tenant when a tenant
  registers (`POST /api/tenants`). Scoped to that tenant only; can manage
  their own store's catalog, orders, bookings, etc.
- **`super-admin`** — platform-wide (`tenant_id = null`). Required for
  `role:super-admin`-gated routes such as `/api/admin/merchants/*`
  (approve/suspend tenants), and for using the `X-Tenant-ID` header to act
  on behalf of any tenant. **Not seeded or assigned by default.** To grant
  it:

  ```bash
  php artisan db:seed --class="App\\Modules\\RBAC\\Database\\Seeders\\RolesAndPermissionsSeeder"
  php artisan tinker
  >>> $user = \App\Models\User::where('email', 'owner@example.test')->first();
  >>> $user->tenant_id = null; // super-admins are platform-level, not tenant-scoped
  >>> $user->save();
  >>> $user->assignRole(\App\Modules\RBAC\Models\Role::withoutGlobalScopes()->where('name', 'super-admin')->whereNull('tenant_id')->first());
  ```

Route-level enforcement uses the `role`, `permission`, and
`role_or_permission` middleware aliases (from `spatie/laravel-permission`),
registered in `bootstrap/app.php`.

## Environment variables of note

See `.env.example` for the full list. Key groups:

- `DB_*` — Postgres connection. The app **must** run as a non-superuser
  role (`marketplace_app` by default) for Row-Level Security to be
  enforced; `DB_MIGRATE_*` is a separate superuser-adjacent role used only
  by Docker's init script and isolation tests, never for app runtime.
- `REDIS_*` — used for `SESSION_DRIVER`, `CACHE_STORE`, and `QUEUE_CONNECTION`.
- `MEILISEARCH_HOST` / `MEILISEARCH_KEY` — marketplace/product search.
- `AWS_*` — S3-compatible storage config (points at local MinIO by default).
- `MAIL_*` — defaults to the `log` driver locally (emails written to the log instead of sent).

## License

MIT (inherited from the Laravel skeleton this project was scaffolded from).
