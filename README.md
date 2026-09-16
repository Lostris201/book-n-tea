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

## Secrets

All secrets live in `backend/.env` only (never committed). `frontend/.env.local` holds only the public API URL.
