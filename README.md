# Book n Tea

Café QR menu + table ordering system. See [`BACKEND_PLAN.md`](BACKEND_PLAN.md) for the full rebuild plan.

```
backend/    Laravel 12 — API + admin panel (Filament 5)
frontend/   Next.js (App Router) — customer QR menu + staff board
legacy/     original static site + Express server, kept running until cutover
docs/
```

## Requirements

- PHP 8.2+ with `intl`, `zip`, `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`
- Composer 2
- Node.js 20+
- MySQL 8 / MariaDB (production; local dev may use SQLite)

## Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env        # then fill in DB_* etc. For local dev set APP_ENV=local, APP_DEBUG=true
php artisan key:generate
php artisan migrate --seed
php artisan storage:link    # serves uploaded product images from /storage
php artisan serve           # http://localhost:8000
```

Admin panel (Filament): http://localhost:8000/admin

| Role | Panel access |
|---|---|
| admin | everything: menu, tables, orders, reservations, users, settings, audit log |
| manager | menu (products, categories, option groups), tables + QR printing, orders, reservations |
| staff | no panel access; uses the staff order board |

Every change to menu items, tables, order status, reservations, settings, users and integrations is recorded in
the audit log (`/admin/activities`) with the user who made it. QR tokens, passwords, integration credentials and
customer phone numbers are never written to it. Set `QR_MENU_URL` (customer menu base URL) before printing QR codes.

Tests: `php artisan test`

Local dev logins (seeded only when `APP_ENV=local`): `admin@bookntea.test`, `manager@bookntea.test`,
`staff@bookntea.test` — password `password`.

### API

| Method | Path | Auth |
|---|---|---|
| POST | `/api/auth/login` `{email, password}` → `{token}` | public (5/min per IP) |
| GET | `/api/auth/me`, POST `/api/auth/logout` | Bearer token |
| GET | `/api/menu` | public |
| POST | `/api/menu` (legacy admin blob, transitional) | admin |
| GET | `/api/tables/resolve?t={qr_token}` → `{number, name}` | public (30/min per IP) |
| GET | `/api/tables` (includes QR tokens) | admin / manager |
| POST | `/api/orders` `{table_token, items:[{product_id, qty, option_ids}], note}` | public (10/min per IP and per table) |
| GET | `/api/orders[?status=]` | staff / manager / admin |
| PATCH | `/api/orders/{id}` `{status}` | staff / manager / admin |
| POST | `/api/waiter-calls` `{table_token, type: waiter\|bill, reason?}` | public (10/min per IP and per table) |
| GET | `/api/waiter-calls` (pending) | staff / manager / admin |
| PATCH | `/api/waiter-calls/{id}` (resolve) | staff / manager / admin |

- `product_id` / `option_ids` are the ids returned by `GET /api/menu`. Prices are always computed on the server;
  any price the client sends is ignored.
- Errors are always `{ "error": "Türkçe mesaj" }`. Cross-origin browser access is limited to `CORS_ALLOWED_ORIGINS`.
- Failed logins, rate-limit hits and invalid table tokens go to `storage/logs/security-*.log` (kept 90 days).

### Reservation integration ("köprü")

No real provider is connected yet — which system the café uses is still an open question (see
`BACKEND_PLAN.md` §9). The scaffold is in place so a real integration is one adapter class:

- `app/Integrations/Reservations/ReservationProvider.php` — the contract; register adapters in
  `ReservationProviderManager::PROVIDERS`. Adapters must make HTTP calls through `IntegrationHttp::make()`
  (10 s timeout, retries, redacted logging to `storage/logs/integrations-*.log`).
- `NullProvider` (disabled) and `FakeProvider` (tests/demo; its webhook format is our own, not a real API).
- Inbound: `POST /api/webhooks/reservations/{provider}` — signature verified with `RESERVATION_WEBHOOK_SECRET`
  (failures → 401 + security log), processed in a queued job, idempotent on `(external_provider, external_id)`.
- Polling: `php artisan reservations:sync` runs every 5 minutes via the scheduler.
- Outbound: reservations created/changed in the panel are pushed by a queued job (5 tries, backoff).
- Conflicts: the provider wins for external reservations; we win for native ones.
- Admin page `/admin/integrations`: enable/disable, test connection, sync now, last sync / last error.
  Secrets are only shown as "Ayarlı" / "Ayarlı değil".

Try it locally: set `RESERVATION_PROVIDER=fake` and `RESERVATION_WEBHOOK_SECRET=...`, enable it on the
Integrations page, then click "Şimdi senkronize et" to import three demo reservations.

Production needs a queue worker (`php artisan queue:work`) and the scheduler cron
(`* * * * * php artisan schedule:run`).

## Frontend (Next.js)

```bash
cd frontend
npm install
cp .env.example .env.local  # NEXT_PUBLIC_API_URL = Laravel base URL
npm run dev                 # http://localhost:3000
npm run build               # static export → frontend/out/
```

| Route | What |
|---|---|
| `/?t={qr_token}` | Customer menu. Without a valid token it asks the guest to scan the table QR (browse-only mode available). |
| `/staff/` | Staff login (Sanctum session cookie) → live order board: 2 s polling, sound + flash on new orders, added items and waiter calls. |

The design is the legacy menu/staff CSS ported as-is (`app/(menu)/menu.css`, `app/(staff)/staff/staff.css`); each
area has its own root layout so the two stylesheets never mix. The menu is always fetched at runtime, so admin
changes appear without a rebuild. Carts live in `sessionStorage` per table.

Production (static export on the VPS):

- Serve `frontend/out/` with Nginx (`try_files $uri $uri/ =404;` — `trailingSlash` is on, so `/staff/` is a folder).
- `NEXT_PUBLIC_API_URL` is baked in at build time; rebuild after changing it.
- Backend `.env`: `SANCTUM_STATEFUL_DOMAINS` = the frontend host, `CORS_ALLOWED_ORIGINS` = the frontend origin,
  `SESSION_DOMAIN` = a parent domain shared by frontend and API (e.g. `.example.com`), `SESSION_SECURE_COOKIE=true`.
- `QR_MENU_URL` = the frontend URL (e.g. `https://menu.example.com/`).

## Legacy site

```bash
cd legacy
npm install
npm start                   # http://localhost:3000 (use PORT=3001 if Next.js is running)
```

Pages: `index.html?masa=N` (menu), `staff.html`, `admin.html`, `qr.html`.

To run the legacy pages against Laravel instead of `server.js`, set `window.BNT_API_BASE = "http://localhost:8000"`
in `legacy/config.js` and add the legacy origin (e.g. `http://localhost:3001`) to `CORS_ALLOWED_ORIGINS` in
`backend/.env`. The staff board then asks for a staff login (browser prompt) and stores the token in localStorage.
In Laravel mode, `qr.html` asks for an admin/manager login and prints `index.html?t={qr_token}` codes; the customer
menu needs that `?t=` link (`?masa=N` only works with `server.js`).

## Secrets

All secrets live in `backend/.env` only (never committed). `frontend/.env.local` holds only the public API URL.
