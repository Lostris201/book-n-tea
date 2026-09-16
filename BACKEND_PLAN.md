# Book n Tea — Backend & Panel Plan

> For Claude Code. Read this whole file first, then read the existing code (`server.js`, `script.js`, `admin.js`, `staff.js`, `qr.js`) before changing anything. Work phase by phase. Do not start a phase until the previous one's acceptance criteria pass. Ask before making decisions marked **OPEN**.

---

## 1. Context

Book n Tea is a café QR menu + table ordering system. A customer scans a table QR, browses the menu, orders; staff see orders on a live board; an admin manages the menu.

Client requirements (translated from the brief):

- Build the admin panel with **Laravel** (PHP backend).
- Build the customer-facing frontend with **Next.js**.
- Keep **logs**.
- Store all keys/secrets in a **`.env`** file; keep `.env`, logs, and internals **unreachable from the web** (brief says `.htaccess`).
- Integrate with the café's existing **reservation system** via its API, keeping the API key secure. The order module is sold to cafés as an add-on that **bridges** their existing panel and ours.

---

## 2. Current state (what's in the repo today)

| File | What it does |
|---|---|
| `index.html` / `script.js` / `styles.css` | Customer QR menu + cart. Reads table from `?masa=` / `?table=`. `POST /api/orders`. Fetches `GET /api/menu`, falls back to localStorage then hardcoded `MENU_DATA`. Has a call-waiter button. |
| `staff.html` / `staff.js` | Staff order board. Polls `GET /api/orders` every 2s. Advances status via `PATCH /api/orders/:id`. Sound alert on new orders. |
| `admin.html` / `admin.js` / `admin.css` | Admin panel (~1600 lines JS). All data in `localStorage` key `bnt_admin_data_v1`; `saveAll()` also POSTs the full blob to `/api/menu`. Manages products, categories, options, tables, staff, settings. |
| `qr.html` / `qr.js` / `vendor/qrcode.js` | Generates printable per-table QR codes pointing at `index.html?masa=N`. |
| `server.js` | Express, ~200 lines. Stores menu + orders as JSON files in `/tmp` on Vercel. |
| `vercel.json` | Routes `/api/*` to `server.js`, everything else static. |

### Known problems (these are the reason for the rebuild)

1. **No persistence.** Vercel `/tmp` is wiped on cold start/redeploy. Admin edits live only in one browser's localStorage.
2. **Fake auth.** `admin.js` checks `admin` / `şifre123` client-side. Visible to anyone. Repo has been public.
3. **Open API.** No auth on any endpoint. Anyone can `POST /api/menu` and replace the menu or `PATCH` any order. CORS is `*`.
4. **Client-trusted prices.** Orders send `name` + `price` from the browser; the server stores whatever it receives.
5. **Guessable table URLs.** `?masa=5` lets anyone order to any table from anywhere.
6. **Inconsistent table count.** Admin seeds exactly 12 tables; `qr.js` uses `TABLE_COUNT = 20`.
7. **No reservation system exists at all.** There is nothing to integrate yet on our side.

---

## 3. Target architecture

```
book-n-tea/
├── backend/          # Laravel 11 — API + admin panel (Filament)
├── frontend/         # Next.js (App Router) — customer QR menu + staff board
├── legacy/           # current static site + server.js, moved here, kept working until cutover
└── docs/
```

- **Laravel = single source of truth.** Database, auth, business logic, logs, third-party integrations, all secrets.
- **Admin panel = Filament** inside Laravel. Replaces `admin.html`/`admin.js`.
- **Next.js = thin client.** Talks only to the Laravel API. Holds **no secrets**.
- **Third-party APIs (reservation provider) are only ever called from Laravel.** Never from the browser, never from Next.js client components.

### Stack

- PHP 8.2+, Laravel 11, MySQL 8 (or MariaDB — whatever the host has)
- Filament v3 (admin panel)
- Laravel Sanctum (staff/admin auth for API + SPA)
- `spatie/laravel-activitylog` (audit log)
- Next.js (App Router), TypeScript, Tailwind — port existing visual design from `styles.css` / `staff.css`, do not redesign

### OPEN — hosting

Hosting is unknown. The `.htaccess` requirement implies Apache / shared hosting (cPanel). Confirm before Phase 6:

- **Shared hosting (cPanel, Apache):** Laravel in a folder *outside* `public_html`, only `backend/public` contents exposed. Next.js as static export (`output: 'export'`) served from `public_html` if it doesn't need SSR. `.htaccess` rules required (see §8).
- **VPS:** Nginx, document root = `backend/public`, Next.js run with `pm2` or Docker. `.htaccess` irrelevant; equivalent Nginx deny rules instead.
- Vercel for the Laravel side is **not** an option.

---

## 4. Database schema

All tables get `id`, `created_at`, `updated_at`. Money stored as **integer kuruş** (`price_cents`), never float.

```
users                id, name, email, password, role enum(admin, manager, staff), is_active
categories           id, slug (unique), name, icon, sort_order, is_active
products             id, category_id FK, slug, name, description, image_path, price_cents,
                     is_active, is_bestseller, is_new, sort_order
option_groups        id, key enum(milk, sugar, extras) — or free string, name, is_multi_select
options              id, option_group_id FK, name, price_cents, sort_order, is_active
product_option_group product_id FK, option_group_id FK   (replaces productOptionMappings)
cafe_tables          id, number (unique), name, qr_token (unique, random 32 chars), is_active
orders               id, public_id (e.g. ord_xxx), cafe_table_id FK, status enum(new, preparing,
                     ready, delivered, done), note (max 1000), has_new_items bool,
                     total_cents, closed_at nullable
order_items          id, order_id FK, product_id FK nullable, name_snapshot, options_snapshot json,
                     unit_price_cents, qty, is_new bool
waiter_calls         id, cafe_table_id FK, type enum(waiter, bill), resolved_at nullable
reservations         id, source enum(native, external), external_provider nullable,
                     external_id nullable, customer_name, customer_phone, party_size,
                     reserved_for datetime, cafe_table_id FK nullable,
                     status enum(pending, confirmed, seated, cancelled, no_show), note
                     unique(external_provider, external_id)
integrations         id, provider (unique), is_enabled, config json (encrypted cast),
                     last_synced_at nullable, last_error text nullable
settings             key (unique), value json    — cafe name, slogan, phone, address, instagram,
                     hours, call_waiter_enabled, request_bill_enabled, sound_notification
activity_log         (from spatie package)
```

Notes:
- `order_items.name_snapshot` / `unit_price_cents` preserve what the customer actually paid even if the product is later edited or deleted.
- Staff list in current `admin.js` (name, role, phone, status) maps to `users` with roles. Do not seed the fake names in production.
- `integrations.config` uses Laravel's `encrypted:array` cast so credentials are encrypted at rest with `APP_KEY`. The provider API key itself should still prefer `.env` (see §7); `config` is for per-café settings if this becomes multi-tenant.

### Seeders
Port the existing seed data from `admin.js` `INITIAL_SEED` (products, categories, options, product→option mappings, settings, 12 tables) into `DatabaseSeeder`. Convert prices ×100 to cents. Generate `qr_token` for each table.

---

## 5. API

Base: `/api`. JSON only. All errors: `{ "error": "Türkçe mesaj" }` with correct HTTP status (keep Turkish error messages — the UI shows them).

### 5a. Legacy-compatible endpoints (Phase 2)

Reproduce `server.js` behavior **exactly** so the existing static frontend works unchanged when pointed at Laravel. Response shapes must match what `script.js` and `staff.js` currently consume.

| Method | Path | Auth | Behavior to preserve |
|---|---|---|---|
| GET | `/api/menu` | public | Returns `{ products, categories, options, productOptionMappings, settings }` in the **same shape as `bnt_admin_data_v1`** (prices as TL numbers, string ids like `tea_1` → use slug). Only active products/categories. |
| POST | `/api/menu` | **admin (Sanctum)** | Was public. Now requires auth. Kept only for transition; Filament replaces it. Can return 410 after cutover. |
| GET | `/api/orders` | **staff** | Default: all orders with `status != done`. `?status=X` filters to that status. Sorted by `created_at` asc. |
| POST | `/api/orders` | public (table-scoped, see 5b) | Body `{ table, items: [{name, price, qty}], note }`. Validation: table + ≥1 item required. **Merge rule:** if the table already has an order with `status != done`, set all existing items `is_new=false`, append new items with `is_new=true`, append note as `existing + " | " + new`, set `status=new`, `has_new_items=true`, return 200. Otherwise create order, items `is_new=false`, return 201. |
| PATCH | `/api/orders/{public_id}` | **staff** | Body `{ status }`, allowed: `new, preparing, ready, delivered, done`. 400 invalid, 404 missing. When `done`, set `closed_at`. |

### 5b. Hardened endpoints (Phase 3 — replace legacy behavior)

- **Table resolution by token:** QR codes encode `/?t={qr_token}` instead of `?masa=N`. `GET /api/tables/resolve?t=...` → `{ number, name }` or 404. `POST /api/orders` accepts `table_token` instead of `table`.
- **Server-side pricing:** `POST /api/orders` items become `[{ product_id, qty, option_ids: [] }]`. Server looks up product + options, rejects inactive ones, computes `unit_price_cents` and `total_cents`. **Never trust client prices.** Snapshot name/options into `order_items`.
- **Rate limit** public order endpoint (e.g. 10/min per IP + per table token).
- `POST /api/waiter-calls` `{ table_token, type: waiter|bill }` (public, rate-limited). `GET` / `PATCH` for staff.
- CORS: only the Next.js origin(s) from `.env`, not `*`.

### 5c. Staff realtime
Phase 2–4: keep 2s polling (it works). Optional later: Laravel Reverb / Pusher broadcasting for `OrderCreated`, `OrderUpdated`, `WaiterCalled`. Don't block on this.

---

## 6. Admin panel (Filament)

Resources:
- **Products** — image upload (store in `storage/app/public/products`, `php artisan storage:link`), price input in TL converted to cents, category select, option group multi-select, active/bestseller/new toggles, drag sort.
- **Categories** — drag sort (`sort_order`), icon field.
- **Option groups / Options**
- **Tables** — number, name, active; action "Regenerate QR token"; page/action to download printable QR sheet (port `qr.html` print layout).
- **Orders** — list + filters by status/table/date, view items, read-only totals. Daily revenue widget.
- **Reservations** — calendar/list view, create native reservations, show source badge (native/external).
- **Users** — admin only. Roles: `admin` (everything), `manager` (menu, tables, orders, reservations), `staff` (orders board + waiter calls only, no Filament access except maybe orders).
- **Settings** — single settings page (café info, feature toggles).
- **Integrations** — admin only: enable/disable provider, "Test connection", show `last_synced_at` / `last_error`. **Never display the API key** — only show whether it's set.
- **Activity log** — admin only, read-only.

Dashboard widgets: open orders count, today's orders/revenue, today's reservations, pending waiter calls (ports the stats in `admin.js`).

---

## 7. Secrets & environment

- Every secret lives in `backend/.env`. Commit only `backend/.env.example` with empty values.
- Access secrets **only through `config/*.php`** files (`config('services.reservation.key')`), never `env()` outside config (breaks with `config:cache`).
- `frontend/.env.local` holds only the **public** API base URL. Nothing secret goes in Next.js env vars. Never prefix a secret with `NEXT_PUBLIC_`.
- Verify `.gitignore` covers `.env`, `.env.*` (except `.env.example`), `storage/logs`, `node_modules`, `vendor`, `.next`.

`backend/.env.example`:
```env
APP_NAME="Book n Tea"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

LOG_CHANNEL=stack
LOG_STACK=daily,security
LOG_LEVEL=info

SANCTUM_STATEFUL_DOMAINS=
SESSION_DOMAIN=
FRONTEND_URL=
CORS_ALLOWED_ORIGINS=

RESERVATION_PROVIDER=
RESERVATION_API_BASE_URL=
RESERVATION_API_KEY=
RESERVATION_API_SECRET=
RESERVATION_WEBHOOK_SECRET=
```

`APP_DEBUG=false` in production is mandatory — debug pages leak env values.

---

## 8. Logging

Three layers:

1. **Application log** — Laravel `daily` channel, `storage/logs/laravel-YYYY-MM-DD.log`, keep 14 days.
2. **Security log** — custom `security` channel (separate daily file, keep 90 days): failed logins, rate-limit hits, invalid table tokens, webhook signature failures, integration auth errors.
3. **Audit log** — `spatie/laravel-activitylog` on Product, Category, Option, CafeTable, Order (status changes), Reservation, Setting, User, Integration (log that config changed, **never log the values**). Record causer (user).

Rules:
- Never log API keys, passwords, tokens, full request bodies of integration calls, or customer phone numbers in plain app logs. Add a log redaction helper for integration HTTP calls (mask `Authorization`, `api_key`, etc.).
- Logs live in `storage/logs`, which is outside the web root.

### Hiding internals — `.htaccess` (shared hosting only)

Primary protection is **document root = `backend/public`**. If the host forces the project root to be web-accessible, add `backend/.htaccess`:

```apache
# Deny everything at project root; only public/ should ever be served
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>

<FilesMatch "^\.">
    Require all denied
</FilesMatch>

<FilesMatch "\.(env|log|sql|sqlite|bak|md|json|lock|yml|yaml|xml|sh)$">
    Require all denied
</FilesMatch>

RedirectMatch 404 ^/(storage|vendor|bootstrap|config|database|routes|tests|app|resources)(/|$)
Options -Indexes
```

And keep Laravel's default `backend/public/.htaccess`, adding:
```apache
Options -Indexes
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
```

**Acceptance check:** after deploy, `curl` these must all return 403/404: `/.env`, `/storage/logs/laravel.log`, `/composer.json`, `/vendor/`, `/.git/config`.

On VPS/Nginx the equivalent is `location ~ /\. { deny all; }` plus correct root.

---

## 9. Reservation integration ("köprü")

### Status
There is **no reservation system in this codebase**, and we don't yet know what the café uses. **OPEN:** friend must ask the café which reservation/POS system they use, then contact that company to request API access (partner/developer API key, docs, sandbox, webhook support).

Possible answers and what they mean:
- **A platform/POS with an API** → build an adapter for it (below).
- **Phone / Instagram / WhatsApp / paper** → no API exists. Native reservations in our panel are the product.
- **A platform that won't give API access** → integration isn't possible; don't sell a bridge for it.

### Design: build native first, adapter-ready

Build native reservations (Phase 4) regardless. Make external providers pluggable so a real integration is a single class later.

```php
// app/Integrations/Reservations/ReservationProvider.php
interface ReservationProvider
{
    public function testConnection(): bool;

    /** @return iterable<ExternalReservationData> */
    public function fetchReservations(CarbonInterface $from, CarbonInterface $to): iterable;

    public function pushReservation(Reservation $reservation): string; // returns external_id

    public function updateStatus(Reservation $reservation, string $status): void;

    public function verifyWebhook(Request $request): bool;

    public function parseWebhook(Request $request): ExternalReservationData;
}
```

- `ReservationProviderManager` resolves the implementation from `config('services.reservation.provider')`.
- Ship a `NullProvider` (integration disabled) and a `FakeProvider` (for tests + demo).
- **Inbound:** `POST /api/webhooks/reservations/{provider}` — verify signature with `RESERVATION_WEBHOOK_SECRET`, reject + security-log on failure, upsert by `(external_provider, external_id)` (idempotent), respond fast, process in a queued job.
- **Polling fallback:** scheduled `reservations:sync` command every 5 min if the provider has no webhooks.
- **Outbound:** when a reservation is created/changed in our panel and the provider supports it, queued job pushes it; store `last_error` on `integrations` on failure, retry with backoff.
- All outbound HTTP through Laravel `Http::` client with timeout (10s), retries, and redacted logging.
- **Conflict rule:** OPEN — which system wins on conflicting edits. Default proposal: the provider is source of truth for external reservations; ours for native.

Order module linkage (the product being sold): when a reservation is `seated`, optionally attach it to the table so orders at that table show the reservation name on the staff board.

---

## 10. Frontend (Next.js)

- Port the **existing design** from `index.html`/`styles.css` and `staff.html`/`staff.css`. Keep all Turkish copy. Do not redesign.
- Routes:
  - `/` — customer menu. Requires `?t={qr_token}`; resolve via API; if missing/invalid show the "scan the QR at your table" error (current spec behavior — no default table).
  - `/staff` — login (Sanctum SPA cookie auth) → order board with 2s polling, status buttons, sound + visual alert on genuinely new orders or `has_new_items`, waiter call list.
- Menu data: fetch from `/api/menu` at runtime (menu changes must appear without redeploy). Remove all localStorage menu logic.
- Cart may stay in localStorage/sessionStorage (per device, non-sensitive).
- API client in `lib/api.ts`; base URL from `NEXT_PUBLIC_API_URL` (this one is public on purpose — it's just a URL).

---

## 11. Phases

### Phase 0 — Setup
- Move current site into `legacy/` (keep it runnable with `node legacy/server.js`).
- Scaffold `backend/` (Laravel 11) and `frontend/` (Next.js).
- `.gitignore`, `.env.example` files, README with local run steps.
- **Accept:** both apps boot locally; legacy still works.

### Phase 1 — Database + seed
- Migrations + models + relations + casts for §4.
- Seeders from `admin.js` `INITIAL_SEED`.
- **Accept:** `php artisan migrate:fresh --seed` works; tinker shows 12 tables with tokens, all products with options.

### Phase 2 — Legacy-compatible API
- Implement §5a exactly. Sanctum auth for staff/admin endpoints.
- Feature tests covering: menu shape, order create (201), order merge on same table (200, `is_new` flags, note join with `" | "`), status filter default excludes `done`, sort order, invalid status 400, unknown order 404, unauthenticated staff endpoints 401.
- **Accept:** tests pass; legacy frontend pointed at Laravel (change fetch base) places and advances orders end-to-end.

### Phase 3 — Hardening
- §5b: table tokens, server-side pricing, rate limiting, CORS lockdown, waiter calls.
- Update legacy `qr.js` to encode tokens (or generate QR sheet from Filament).
- **Accept:** tampered price in request has no effect on stored total; invalid token → 404; 11th order in a minute → 429.

### Phase 4 — Filament admin + logging
- §6 resources, roles/policies, dashboard widgets.
- §8 logging channels + activity log + redaction.
- Native reservations CRUD.
- **Accept:** manager can edit a product and the customer menu reflects it on refresh; staff user cannot open admin resources; activity log records the edit with causer.

### Phase 5 — Reservation integration scaffold
- §9 interface, manager, `NullProvider`, `FakeProvider`, webhook endpoint, sync command, queued jobs, Integrations admin page with "Test connection".
- **Accept:** with `FakeProvider`, webhook with bad signature → 401 + security log; valid webhook twice → one reservation (idempotent); sync command imports fake reservations.
- Real provider adapter is **blocked** until §9 OPEN is resolved.

### Phase 6 — Next.js frontend
- §10. Port designs. Customer menu + staff board against Laravel.
- **Accept:** full flow on a phone: scan QR → order → staff board alerts → status updates.

### Phase 7 — Deploy
- Resolve hosting OPEN (§3). Apply §8 hiding rules.
- `php artisan config:cache route:cache view:cache`, `APP_DEBUG=false`, queue worker (or `QUEUE_CONNECTION=database` + cron `schedule:run` on shared hosting).
- Retire `legacy/` and `vercel.json` after cutover.
- **Accept:** §8 curl checks all 403/404; HTTPS enforced; new orders persist across server restart.

---

## 12. Rules for Claude Code

- Don't invent the reservation provider, its endpoints, or its payload format. Use the interface + `FakeProvider` until real docs exist.
- Don't change visual design or Turkish UI copy unless asked.
- Don't commit anything(ask the user).
- Don't commit secrets, real customer data, or `.env`.
- Money in integer cents everywhere in the backend; convert to TL only at the API/UI boundary.
- Write feature tests for every API endpoint and every business rule stated in this file.
- When a requirement here conflicts with existing code, this file wins; when it conflicts with something the user says in chat, the user wins.
- Items marked **OPEN** need a human answer — stop and ask.

## 13. Open questions (for the café / friend)

1. Which reservation system does the café use now? Does that company offer an API and webhooks?
2. Hosting: shared (cPanel) or VPS? Existing domain?
3. Real table count (admin says 12, QR page says 20)?
4. Does the Next.js customer menu need SSR, or is a static export fine?
5. Is this single café, or should the backend be multi-tenant (the "sell as add-on to other cafés" angle)? This changes the schema (add `cafe_id` everywhere) — decide **before Phase 1**.
