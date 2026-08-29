# Table Order Notifications & QR Codes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give each cafe table a scannable QR code that opens the customer menu pre-loaded with that table's number, make scanning mandatory (no silent fallback), and alert staff with sound + a visual flash when a new order arrives on the staff panel.

**Architecture:** Pure additions/edits to the existing flat static-file + Express app (`index.html`/`script.js` for customers, `staff.html`/`staff.js` for staff). No build step, no framework, no new backend routes — `express.static` already serves any file placed in the repo root. One small third-party QR-rendering library is vendored (downloaded once, committed) so QR generation never depends on a live external service.

**Tech Stack:** Vanilla HTML/CSS/JS, Express (existing), `qrcode-generator` (vendored JS library, MIT licensed), Web Audio API (native browser API, no library).

## Global Constraints

- This is a test/tryout deployment — prioritize simplicity over robustness (explicit user decision).
- Storage stays in-memory (`ordersMemory` in `server.js`) — no database work in this plan.
- No push notifications / service worker — sound + visual alert only work while the staff panel tab is open.
- QR generation must not depend on a live external API at runtime — the library is vendored into the repo.
- Table count is a single constant (`TABLE_COUNT = 20`), covering the confirmed 11–20 tables with headroom.
- All user-facing copy is Turkish, matching the existing tone (`script.js`, `staff.js` strings).
- No automated test framework is being introduced (none exists today — `package.json` has no test script). Verification uses the Playwright browser MCP tools already available in this environment to drive real browser checks against a locally running server, matching the manual testing plan in the design spec but executed reproducibly instead of by eyeballing.
- Follow the existing flat file layout (no subfolders for hand-written app files) except for the one vendored library, which goes in `vendor/` to keep it visually distinct from hand-written code.

---

## Task 1: Vendor the QR code generation library

**Files:**
- Create: `vendor/qrcode.js`

**Interfaces:**
- Produces: a global `qrcode(typeNumber, errorCorrectionLevel)` factory function. Calling it returns an object with `.addData(text)`, `.make()`, and `.createSvgTag(cellSize, margin)` (returns an `<svg>...</svg>` string). Task 2 consumes this exact API.

- [ ] **Step 1: Download the pinned library file**

Run:

```bash
mkdir -p "V:/iş/book-n-tea/vendor"
curl -L -o "V:/iş/book-n-tea/vendor/qrcode.js" "https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"
```

This is `qrcode-generator` by Kazuhiko Arase (MIT license), pinned to version `1.4.4` so the file never changes underneath the project.

- [ ] **Step 2: Verify the downloaded file**

Run:

```bash
head -c 400 "V:/iş/book-n-tea/vendor/qrcode.js"
grep -c "createSvgTag" "V:/iş/book-n-tea/vendor/qrcode.js"
```

Expected: the header comment block starts with `QR Code Generator for JavaScript` / `Copyright (c) 2009 Kazuhiko Arase`, and `grep -c` reports at least `1` match (the `createSvgTag` function must be present — it's what Task 2 renders QR codes with).

- [ ] **Step 3: Commit**

```bash
cd "V:/iş/book-n-tea"
git add vendor/qrcode.js
git commit -m "Vendor qrcode-generator library for client-side QR rendering

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Task 2: Build the staff QR codes page

**Files:**
- Create: `qr.html`
- Create: `qr.css`
- Create: `qr.js`
- Modify: `staff.html` (add a link to the new page)
- Modify: `staff.css` (style the new link)

**Interfaces:**
- Consumes: `vendor/qrcode.js`'s global `qrcode(typeNumber, errorCorrectionLevel)` / `.addData()` / `.make()` / `.createSvgTag(cellSize, margin)` from Task 1.
- Produces: nothing consumed by later tasks — this is a self-contained staff-facing page.

- [ ] **Step 1: Create `qr.html`**

```html
<!DOCTYPE html>
<html lang="tr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Book n Tea — Masa QR Kodları</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Outfit:wght@300;400;500;600&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="qr.css" />
  </head>
  <body>
    <div class="qr-page">
      <header class="qr-header">
        <p class="qr-header__eyebrow">Book n Tea</p>
        <h1 class="qr-header__title">Masa QR Kodları</h1>
        <p class="qr-header__hint">
          Yazdırmak için Ctrl/Cmd + P kullanın — her masa kendi sayfasına basılır.
        </p>
        <a class="qr-header__back" href="staff.html">← Kafe paneline dön</a>
      </header>
      <main class="qr-grid" id="qrGrid"></main>
    </div>
    <script src="vendor/qrcode.js"></script>
    <script src="qr.js"></script>
  </body>
</html>
```

- [ ] **Step 2: Create `qr.css`**

```css
:root {
  --green-deep: #243d32;
  --cream: #f2ebe0;
  --cream-muted: #d6ccbc;
  --ink: #f7f2ea;
  --ink-dim: rgba(247, 242, 234, 0.7);
  --line: rgba(196, 160, 122, 0.28);
  --brown-warm: #9a6f48;
  --font-display: "Cormorant Garamond", Georgia, serif;
  --font-body: "Outfit", system-ui, sans-serif;
  --ease: cubic-bezier(0.22, 1, 0.36, 1);
}

*,
*::before,
*::after {
  box-sizing: border-box;
}

body {
  margin: 0;
  min-height: 100dvh;
  font-family: var(--font-body);
  font-weight: 300;
  color: var(--ink);
  background: var(--green-deep);
  -webkit-font-smoothing: antialiased;
}

.qr-page {
  max-width: 72rem;
  margin: 0 auto;
  padding: 1.5rem 1.25rem 3rem;
}

.qr-header {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--line);
}

.qr-header__eyebrow {
  margin: 0 0 0.25rem;
  font-size: 0.8rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--brown-warm);
}

.qr-header__title {
  margin: 0 0 0.5rem;
  font-family: var(--font-display);
  font-size: clamp(2rem, 5vw, 2.75rem);
  font-weight: 600;
  color: var(--cream);
}

.qr-header__hint {
  margin: 0 0 0.5rem;
  font-size: 0.9rem;
  color: var(--ink-dim);
}

.qr-header__back {
  font-size: 0.85rem;
  color: var(--cream-muted);
}

.qr-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr));
  gap: 1.25rem;
}

.qr-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1.25rem;
  background: rgba(0, 0, 0, 0.18);
  border: 1px solid var(--line);
  text-align: center;
}

.qr-card__number {
  margin: 0;
  font-family: var(--font-display);
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--cream);
}

.qr-card__code {
  background: #fff;
  padding: 0.5rem;
  line-height: 0;
}

.qr-card__code svg {
  display: block;
  width: 10rem;
  height: 10rem;
}

.qr-card__url {
  margin: 0;
  font-size: 0.7rem;
  color: var(--ink-dim);
  word-break: break-all;
}

@media print {
  body {
    background: #fff;
    color: #000;
  }

  .qr-header__back {
    display: none;
  }

  .qr-grid {
    display: block;
  }

  .qr-card {
    page-break-after: always;
    border: none;
    padding: 2rem 0;
  }

  .qr-card__number {
    color: #000;
    font-size: 2rem;
  }

  .qr-card__url {
    color: #333;
  }
}
```

- [ ] **Step 3: Create `qr.js`**

```js
const TABLE_COUNT = 20;
const grid = document.getElementById("qrGrid");

for (let table = 1; table <= TABLE_COUNT; table += 1) {
  grid.appendChild(buildCard(table));
}

function buildCard(table) {
  const url = `${window.location.origin}/index.html?masa=${table}`;

  const qr = qrcode(0, "M");
  qr.addData(url);
  qr.make();

  const card = document.createElement("article");
  card.className = "qr-card";
  card.innerHTML = `
    <p class="qr-card__number">Masa ${table}</p>
    <div class="qr-card__code">${qr.createSvgTag(5, 4)}</div>
    <p class="qr-card__url">${escapeHtml(url)}</p>
  `;
  return card;
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}
```

- [ ] **Step 4: Link to the QR page from the staff panel**

In `staff.html`, find:

```html
        <div class="staff-header__meta">
          <span class="pulse" id="liveDot" aria-hidden="true"></span>
          <span id="orderCount">0 aktif</span>
        </div>
```

Replace with:

```html
        <div class="staff-header__meta">
          <a class="staff-header__qr-link" href="qr.html">QR Kodları</a>
          <span class="pulse" id="liveDot" aria-hidden="true"></span>
          <span id="orderCount">0 aktif</span>
        </div>
```

In `staff.css`, find:

```css
.staff-header__meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: var(--ink-dim);
}

.pulse {
```

Replace with:

```css
.staff-header__meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: var(--ink-dim);
}

.staff-header__qr-link {
  padding: 0.4rem 0.8rem;
  border: 1px solid var(--line);
  color: var(--cream-muted);
  font-size: 0.82rem;
  text-decoration: none;
  transition: background 0.2s var(--ease), color 0.2s var(--ease);
}

.staff-header__qr-link:hover {
  background: rgba(107, 74, 50, 0.4);
  color: var(--ink);
}

.pulse {
```

- [ ] **Step 5: Start the server and verify with a real browser**

Run in the background:

```bash
cd "V:/iş/book-n-tea"
node server.js
```

Wait for `Book n Tea → http://localhost:3000` in its output.

Using the Playwright browser tools:
1. `browser_navigate` to `http://localhost:3000/qr.html`.
2. `browser_evaluate` with `() => document.querySelectorAll('.qr-card').length` — expect `20`.
3. `browser_evaluate` with `() => document.querySelector('.qr-card svg') !== null` — expect `true`.
4. `browser_evaluate` with `() => document.querySelector('.qr-card__url').textContent` — expect it to end with `/index.html?masa=1`.
5. `browser_navigate` to `http://localhost:3000/staff.html`.
6. `browser_evaluate` with `() => document.querySelector('.staff-header__qr-link').getAttribute('href')` — expect `"qr.html"`.

- [ ] **Step 6: Stop the server**

Stop the background `node server.js` process (e.g. via the tool that started it, or `taskkill`/`kill` on its PID).

- [ ] **Step 7: Commit**

```bash
cd "V:/iş/book-n-tea"
git add qr.html qr.css qr.js staff.html staff.css
git commit -m "Add printable per-table QR code page for staff

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Task 3: Make the table number mandatory on the customer menu

**Files:**
- Modify: `index.html`
- Modify: `styles.css`
- Modify: `script.js`

**Interfaces:**
- Consumes: nothing from earlier tasks.
- Produces: nothing consumed by later tasks — independent of Task 2 and Task 4.

- [ ] **Step 1: Add the error markup to `index.html`**

Find:

```html
        <p class="loader__hint">Menü hazırlanıyor…</p>
      </div>
    </div>

    <div class="page is-hidden" id="page">
```

Replace with:

```html
        <p class="loader__hint">Menü hazırlanıyor…</p>
      </div>
    </div>

    <div class="table-error" id="tableError" hidden role="alert">
      <div class="table-error__inner">
        <p class="table-error__icon" aria-hidden="true">❧</p>
        <h1 class="table-error__title">Masa bulunamadı</h1>
        <p class="table-error__text">Lütfen masanızdaki QR kodu okutun.</p>
      </div>
    </div>

    <div class="page is-hidden" id="page">
```

- [ ] **Step 2: Add table-error styles to `styles.css`**

Find:

```css
.loader__hint {
  margin: 0;
  font-size: 0.85rem;
  color: var(--ink-dim);
  letter-spacing: 0.05em;
}

/* —— Page —— */
```

Replace with:

```css
.loader__hint {
  margin: 0;
  font-size: 0.85rem;
  color: var(--ink-dim);
  letter-spacing: 0.05em;
}

/* —— Table error —— */
.table-error {
  position: fixed;
  inset: 0;
  z-index: 90;
  display: grid;
  place-items: center;
  padding: 2rem;
  text-align: center;
  background: linear-gradient(165deg, #355844, var(--green-deep));
}

.table-error__inner {
  max-width: 22rem;
}

.table-error__icon {
  margin: 0 0 0.75rem;
  font-size: 2rem;
  color: var(--green-glow);
}

.table-error__title {
  margin: 0 0 0.5rem;
  font-family: var(--font-display);
  font-size: 1.75rem;
  font-weight: 600;
  color: var(--cream);
}

.table-error__text {
  margin: 0;
  font-size: 0.95rem;
  color: var(--ink-dim);
}

/* —— Page —— */
```

- [ ] **Step 3: Update the table-number logic in `script.js`**

Find:

```js
const params = new URLSearchParams(window.location.search);
const fromUrl = params.get("masa") || params.get("table");
const tableNumber = fromUrl && /^\d+$/.test(fromUrl) ? fromUrl : "1";

let toastTimer = null;

window.addEventListener("load", () => {
  setTimeout(() => {
    loader.classList.add("is-done");
    page.classList.remove("is-hidden");
  }, 1400);
});
```

Replace with:

```js
const tableError = document.getElementById("tableError");

const params = new URLSearchParams(window.location.search);
const fromUrl = params.get("masa") || params.get("table");
const tableNumber = fromUrl && /^\d+$/.test(fromUrl) ? fromUrl : null;

let toastTimer = null;

window.addEventListener("load", () => {
  setTimeout(() => {
    loader.classList.add("is-done");
    if (tableNumber) {
      page.classList.remove("is-hidden");
    } else {
      tableError.hidden = false;
    }
  }, 1400);
});
```

- [ ] **Step 4: Start the server and verify the no-table case**

Run in the background:

```bash
cd "V:/iş/book-n-tea"
node server.js
```

Using the Playwright browser tools:
1. `browser_navigate` to `http://localhost:3000/index.html` (no query string).
2. `browser_wait_for` with `time: 2` (the reveal logic has a 1400ms delay).
3. `browser_evaluate` with `() => document.getElementById('tableError').hidden` — expect `false`.
4. `browser_evaluate` with `() => document.getElementById('page').classList.contains('is-hidden')` — expect `true`.

- [ ] **Step 5: Verify the valid-table case still works**

Using the Playwright browser tools:
1. `browser_navigate` to `http://localhost:3000/index.html?masa=7`.
2. `browser_wait_for` with `time: 2`.
3. `browser_evaluate` with `() => document.getElementById('tableError').hidden` — expect `true`.
4. `browser_evaluate` with `() => document.getElementById('page').classList.contains('is-hidden')` — expect `false`.
5. `browser_click` the first `.item__add` button (adds an item to the cart).
6. `browser_click` `#cartFab`, then `browser_click` `#submitOrder`.
7. `browser_evaluate` with `() => fetch('/api/orders').then(r => r.json()).then(orders => orders.at(-1).table)` — expect `"7"`.

- [ ] **Step 6: Stop the server**

Stop the background `node server.js` process.

- [ ] **Step 7: Commit**

```bash
cd "V:/iş/book-n-tea"
git add index.html styles.css script.js
git commit -m "Require a valid table QR scan instead of defaulting to table 1

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```

---

## Task 4: New-order sound + visual alert on the staff panel

**Files:**
- Modify: `staff.html`
- Modify: `staff.css`
- Modify: `staff.js`

**Interfaces:**
- Consumes: nothing from earlier tasks.
- Produces: nothing consumed by later tasks.

- [ ] **Step 1: Add the sound-enable button to `staff.html`**

Find:

```html
      </header>

      <div class="filters" role="tablist" aria-label="Durum filtresi">
```

Replace with:

```html
      </header>

      <button class="sound-toggle" id="soundToggle" type="button">
        🔈 Sesi Etkinleştir
      </button>

      <div class="filters" role="tablist" aria-label="Durum filtresi">
```

- [ ] **Step 2: Add flash animation and button styles to `staff.css`**

Find:

```css
.pulse {
  width: 0.55rem;
  height: 0.55rem;
  border-radius: 50%;
  background: #7dba8e;
  box-shadow: 0 0 0 0 rgba(125, 186, 142, 0.6);
  animation: pulse 1.8s ease-out infinite;
}

.filters {
```

Replace with:

```css
.pulse {
  width: 0.55rem;
  height: 0.55rem;
  border-radius: 50%;
  background: #7dba8e;
  box-shadow: 0 0 0 0 rgba(125, 186, 142, 0.6);
  animation: pulse 1.8s ease-out infinite;
}

.sound-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin-bottom: 1.25rem;
  padding: 0.55rem 0.95rem;
  border: 1px solid var(--line);
  background: rgba(107, 74, 50, 0.25);
  color: var(--cream);
  font-family: var(--font-body);
  font-size: 0.85rem;
  cursor: pointer;
  transition: background 0.2s var(--ease);
}

.sound-toggle:hover {
  background: rgba(107, 74, 50, 0.45);
}

.sound-toggle.is-enabled {
  display: none;
}

.filters {
```

Then find:

```css
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

Replace with:

```css
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

.order.is-flash {
  animation: order-flash 1.8s ease-out 3;
}

@keyframes order-flash {
  0% {
    box-shadow: 0 0 0 0 rgba(196, 165, 116, 0.9);
  }
  50% {
    box-shadow: 0 0 0 10px rgba(196, 165, 116, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(196, 165, 116, 0);
  }
}
```

- [ ] **Step 3: Add seen-order tracking, alert tone, and flash logic to `staff.js`**

Find:

```js
const board = document.getElementById("board");
const emptyState = document.getElementById("emptyState");
const orderCount = document.getElementById("orderCount");
const filters = document.querySelectorAll(".filter");

let currentFilter = "all";
let orders = [];
```

Replace with:

```js
const board = document.getElementById("board");
const emptyState = document.getElementById("emptyState");
const orderCount = document.getElementById("orderCount");
const filters = document.querySelectorAll(".filter");
const soundToggle = document.getElementById("soundToggle");

let currentFilter = "all";
let orders = [];
let soundEnabled = false;
let audioCtx = null;
let seenOrderIds = null; // null until the first fetch has been processed
const FLASH_DURATION_MS = 5400;
const flashUntil = new Map(); // orderId -> timestamp when the flash should stop

soundToggle.addEventListener("click", () => {
  try {
    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    soundEnabled = true;
  } catch {
    soundEnabled = false;
  }
  soundToggle.classList.add("is-enabled");
});

function playAlertTone() {
  if (!soundEnabled || !audioCtx) return;

  [880, 1046.5].forEach((freq, i) => {
    const oscillator = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    oscillator.type = "sine";
    oscillator.frequency.value = freq;
    gain.gain.setValueAtTime(0.0001, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.2, audioCtx.currentTime + 0.02);
    gain.gain.exponentialRampToValueAtTime(
      0.0001,
      audioCtx.currentTime + 0.25
    );
    oscillator.connect(gain).connect(audioCtx.destination);
    const start = audioCtx.currentTime + i * 0.18;
    oscillator.start(start);
    oscillator.stop(start + 0.25);
  });
}
```

Find:

```js
async function fetchOrders() {
  try {
    const res = await fetch("/api/orders");
    if (!res.ok) throw new Error("okunamadı");
    orders = await res.json();
    render();
  } catch {
    emptyState.textContent = "Sunucuya bağlanılamadı.";
    emptyState.hidden = false;
  }
}
```

Replace with:

```js
async function fetchOrders() {
  try {
    const res = await fetch("/api/orders");
    if (!res.ok) throw new Error("okunamadı");
    orders = await res.json();

    const currentIds = new Set(orders.map((o) => o.id));

    if (seenOrderIds === null) {
      seenOrderIds = currentIds;
    } else {
      const newIds = [...currentIds].filter((id) => !seenOrderIds.has(id));
      if (newIds.length) {
        playAlertTone();
        newIds.forEach((id) => flashUntil.set(id, Date.now() + FLASH_DURATION_MS));
      }
      seenOrderIds = currentIds;
    }

    render();
  } catch {
    emptyState.textContent = "Sunucuya bağlanılamadı.";
    emptyState.hidden = false;
  }
}
```

Find:

```js
    board.appendChild(card);
  });
}
```

Replace with:

```js
    const flashExpiry = flashUntil.get(order.id);
    if (flashExpiry) {
      if (flashExpiry > Date.now()) {
        card.classList.add("is-flash");
      } else {
        flashUntil.delete(order.id);
      }
    }

    board.appendChild(card);
  });
}
```

- [ ] **Step 4: Start the server and verify with a real browser**

Run in the background:

```bash
cd "V:/iş/book-n-tea"
node server.js
```

Using the Playwright browser tools:
1. `browser_navigate` to `http://localhost:3000/staff.html`.
2. `browser_click` `#soundToggle`.
3. `browser_evaluate` with `() => document.getElementById('soundToggle').classList.contains('is-enabled')` — expect `true`.
4. `browser_evaluate` with the following (posts a fresh order directly, simulating a customer submitting one):
   ```js
   () => fetch('/api/orders', {
     method: 'POST',
     headers: { 'Content-Type': 'application/json' },
     body: JSON.stringify({ table: '3', items: [{ name: 'Espresso', price: 70, qty: 1 }] }),
   }).then((r) => r.json())
   ```
5. `browser_wait_for` with `time: 3` (staff.js polls every 2s).
6. `browser_evaluate` with `() => document.querySelector('.order.is-flash') !== null` — expect `true`.

- [ ] **Step 5: Stop the server**

Stop the background `node server.js` process.

- [ ] **Step 6: Commit**

```bash
cd "V:/iş/book-n-tea"
git add staff.html staff.css staff.js
git commit -m "Alert staff with sound and a visual flash when a new order arrives

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>"
```
