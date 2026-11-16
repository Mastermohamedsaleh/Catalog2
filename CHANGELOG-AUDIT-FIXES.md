# CatalogPro – Audit Fixes

## Before deploying
1. `composer install --no-dev`, set a real `.env` (never commit/ship it); rotate the APP_KEY and DB password that were in the original zip.
2. Set `ADMIN_SEED_EMAIL` / `ADMIN_SEED_PASSWORD` in `.env` before `php artisan db:seed --class=AdminUserSeeder` (if unset, a random password is printed once).
3. `php artisan migrate` – runs `2026_11_15_000001_harden_financial_integrity` (back up first). On MySQL it also adds FK restrict rules and CHECK constraints.
   If the migration fails on a unique index, you have duplicate wallets / duplicate phone numbers / duplicate credits: fix the data, rerun.
4. Production: `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`, `php artisan config:cache route:cache view:cache`.

## Fixed
- C1 `Order::booted` hook zeroed product stock -> removed; stock handled by `StockService` (atomic, never negative, variants sync parent).
- C2/H1 wallet credit race and double credit -> `OrderStatusService` (row locks, transition map, delivered/cancelled/returned terminal, one credit per order enforced by a unique index).
- C3 cascade deletes of orders/wallets/ledger -> restrict FKs, soft-deleted orders, deletion refused when history exists.
- C4 stored XSS in customers page -> escaped output.
- C5 any admin could reset/zero/delete a super admin -> 403; last super admin protected.
- H2 `back()->init()` bug, H3 stock restore race, H4 variants recreated on edit (ids now preserved), H5 order deletion rules, H6 merchant order flow (cost from DB, price >= cost, variant required, duplicate guard, throttles), H7 withdrawals (locked, ledgered), H8 no default password / error text no longer leaked, H9 `phone` saved on registration.
- Categories (max 2 levels, safe slugs, safe delete), media (cleanup on failure, size limits), public pages (active-only, N+1 removed), merchant order search is now server-side and paginated.
- Added a `dashboard` route alias (Breeze views referenced it and crashed on /profile).

## Business decisions left to you
- Post-delivery returns / clawback of credited profit are not supported (delivered is final).
- Cost price = product price + variant `price_modifier` (admin form has no modifier field yet).
- Catalog pages require login (as in the original routes).

## Tests
`php artisan test` -> 48 passing (sqlite in-memory): stock, variants, profit math, double-credit, terminal states, delete refusals, access control, XSS, page smoke tests.
