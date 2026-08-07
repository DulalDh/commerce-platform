# API Changelog

The API contract (`docs/postman/marketplace-api.postman_collection.json`) is
frozen at **v1.0.0** as of the Phase 5 hardening pass. This file tracks what
shipped in each phase and the rules for changing the contract from here on.

## Freeze policy

- No breaking changes to a frozen v1 endpoint (removed field, renamed field,
  changed status code, changed auth requirement) without a version bump and
  a documented migration note here.
- Additive, backward-compatible changes (new endpoint, new optional field,
  new response field) are fine within v1.0.x and should still be logged
  below and added to the Postman collection in the same change.
- Any endpoint added after this freeze must include: migration, model,
  request/resource classes, route, a Postman entry, and a passing
  `pest`/`pint`/`phpstan` gate before merging - same bar as every phase so far.

## v1.0.0 — Phase 5 (Hardening)

- No new endpoints. Contract frozen as-is from Phase 4.
- Fixed a critical tenant-isolation bug: `ResolveTenant`'s super-admin check
  used `tenant_id === null`, which is also true of every self-registered
  customer, granting them a full RLS/scope bypass by default and letting
  them impersonate any tenant via `X-Tenant-ID`. No endpoint behavior
  changed for legitimate callers; unauthenticated/under-privileged callers
  now correctly see empty results instead of cross-tenant data.
- Extended Postgres RLS from the `roles` table alone to all ~39 tenant-owned
  tables (previously enforced only at the Eloquent layer).

## Phase 0-4 — pre-freeze

Endpoints were added incrementally per phase with no versioning; the
collection folders are numbered in the order they were built:

1. Tenants, Auth, RBAC — registration, login, tenant profile/KYC, roles/permissions
2. Catalog, Inventory, Orders, Payments, Customers — product marketplace core
3. Super Admin, Marketplace (publish/approve), Subscriptions, Shipping, Promotions
4. Services, Bookings, Reviews — service marketplace
5. Marketplace search, Reports, Notifications, CMS — unification, reporting, comms

See the Postman collection's per-request descriptions and the module route
files under `app/Modules/*/Routes/api.php` for the authoritative, current
list of endpoints.
