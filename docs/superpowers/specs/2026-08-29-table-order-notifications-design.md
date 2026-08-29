# Table Order Notifications & QR Codes — Design

**Date:** 2026-08-29
**Status:** Approved
**Scope:** Test/tryout phase, not production-hardened.

## Context

Book n Tea already has a working order loop:

- `index.html` / `script.js` — customer menu + cart, reads the table number from a
  `?masa=` or `?table=` URL query param, `POST`s orders to `/api/orders`.
- `staff.html` / `staff.js` — staff board that polls `GET /api/orders` every 2s and
  lets staff advance an order through `new → preparing → ready → done` via
  `PATCH /api/orders/:id`.
- `server.js` — Express API, orders held in a plain in-memory array
  (`ordersMemory`), deployed to Vercel (`vercel.json` present).

What's missing relative to "customer orders, notification goes, QR per table,
customer shouldn't type the table number, cook has a panel":

1. No QR codes exist yet for tables.
2. The customer menu silently defaults to table `"1"` if no table param is present,
   so a bare/shared link can misattribute an order.
3. The staff panel re-renders silently on each poll — no sound/visual cue that a
   new order just came in.

This is a **tryout/test deployment**, so simplicity is prioritized over production
robustness (explicitly confirmed with the user):

- Storage stays in-memory. It's known and accepted that a Vercel redeploy or cold
  start can clear active orders during this test phase. `readOrders`/`writeOrders`
  in `server.js` already isolate storage behind two functions, so swapping in a
  real database later is a contained change, not a rewrite.
- No push notifications / service worker — sound + visual on the open staff panel
  is enough.

## Goals

- Each table gets a QR code that opens the customer menu pre-loaded with that
  table's number, with nothing for the customer to type.
- Opening the menu without a valid table number shows an error, not a default
  table.
- The staff panel alerts (sound + visual) when a genuinely new order arrives,
  without re-alerting on orders it already knew about.

## Non-goals

- No database / persistent storage changes.
- No push notifications that work while the staff panel is closed or the device
  is asleep.
- No changes to the menu content, pricing, or existing order-status workflow.
- No print-shop-quality QR design — plain, functional, printable is enough.

## Components

### 1. QR codes per table (`staff/qr.html`, `staff/qr.js`)

- New static page, linked from the staff panel, not customer-facing.
- A single constant `TABLE_COUNT = 20` controls how many tables are generated
  (covers the confirmed 11–20 range with headroom; bump the constant if the cafe
  adds tables).
- For each table `N` from 1 to `TABLE_COUNT`, render a card containing:
  - a large table number,
  - a QR code encoding `` `${location.origin}/index.html?masa=${N}` ``.
- QR codes are generated **client-side** with a small embedded QR-generation
  library (vendored, no runtime network dependency on a third-party QR API) so
  the page works offline/on any host without extra config.
- Because the encoded URL is built from `location.origin` at render time, the
  same page produces correct QR codes whether it's opened on `localhost`, a
  Vercel preview URL, or the final production domain — no hardcoded domain
  anywhere.
- A print stylesheet (`@media print`) lays out one card per table so the page can
  be printed via the browser's own print dialog and the cards cut apart.

### 2. Mandatory table param on the customer menu (`script.js`)

- Current behavior: `fromUrl && /^\d+$/.test(fromUrl) ? fromUrl : "1"` — silently
  falls back to table `"1"`.
- New behavior: if there's no valid numeric table param, do not render the menu.
  Instead show a small inline message in place of the page content, e.g.
  "Lütfen masanızdaki QR kodu okutun." (Please scan the QR code on your table.)
- No other cart/menu/order logic changes.

### 3. New-order alert on the staff panel (`staff.js`)

- `staff.js` already holds `orders` (last fetched array) and calls `render()`
  each poll. Add a `seenOrderIds` `Set<string>` that persists across polls.
- On the **first** successful fetch after page load, populate `seenOrderIds`
  from the initial order list without alerting (so reopening the panel doesn't
  trigger a sound storm for orders already in flight).
- On every subsequent fetch, any order ID not already in `seenOrderIds` is
  treated as new:
  - add it to `seenOrderIds`,
  - play a short alert tone via the Web Audio API (a couple of oscillator beeps;
    no audio file asset to manage),
  - apply a temporary CSS highlight/flash class to that order's card for a few
    seconds.
- Browser autoplay policies block audio until the user has interacted with the
  page. On load, show a small "Sesi Etkinleştir" (enable sound) button; until
  pressed, alerts still render the visual flash, just without sound. Once
  pressed, the button hides and future alerts play sound automatically.

## Data Flow

1. Customer scans the table's printed QR → phone opens
   `index.html?masa=<table>`.
2. Menu renders normally (table param present and valid); customer builds a cart
   and submits → `POST /api/orders` with `{ table, items, note }` (unchanged).
3. Order is appended to `ordersMemory` with `status: "new"` (unchanged).
4. `staff.js`'s next poll (≤2s later) sees an order ID it hasn't seen before →
   flashes the card and plays the alert tone (after sound is enabled once).
5. Staff advances the order through its existing status buttons
   (`PATCH /api/orders/:id`), same as today.

## Error Handling

- Missing/invalid table param → customer sees the "scan the QR code" message
  instead of a broken or misattributed order. No change to server-side
  validation, which already rejects orders missing `table` or `items`.
- Staff panel losing connectivity → existing behavior (shows "Sunucuya
  bağlanılamadı.") is unchanged; the new alert logic only runs on successful
  fetches.
- QR page has no server dependency — it's static and self-contained, so it can't
  fail due to the orders API being down.

## Testing Plan

Manual only, matching the project's current lack of an automated test setup:

1. Open `index.html` with no query param → confirm the scan-QR message appears
   and no menu/cart is shown.
2. Open `index.html?masa=7` → confirm the menu loads and a submitted order
   carries `table: "7"`.
3. Open `staff.html`, then from another tab place a new order → confirm the
   sound (after clicking "Sesi Etkinleştir") and the visual flash both trigger
   for that order only, and that orders already present on load did not alert.
4. Open `staff/qr.html` → confirm 20 distinct QR codes render, and scanning one
   with a phone camera opens the menu on the corresponding table number.
5. Print `staff/qr.html` (print preview is sufficient) → confirm one table card
   per printed page/section, legible at printed size.
