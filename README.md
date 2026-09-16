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
php artisan migrate
php artisan serve           # http://localhost:8000
```

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

## Frontend (Next.js)

```bash
cd frontend
npm install
cp .env.example .env.local  # NEXT_PUBLIC_API_URL = Laravel base URL
npm run dev                 # http://localhost:3000
```

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
