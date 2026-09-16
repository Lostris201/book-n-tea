/* ==========================================================================
   BOOK & TEA HOUSE — Staff / Kitchen Panel
   ========================================================================== */

const board = document.getElementById("board");
const emptyState = document.getElementById("emptyState");
const orderCount = document.getElementById("orderCount");
const filters = document.querySelectorAll(".filter");
const soundToggle = document.getElementById("soundToggle");

// Ödeme modalı elementleri
const paymentModal = document.getElementById("paymentModal");
const paymentModalBackdrop = document.getElementById("paymentModalBackdrop");
const paymentModalTable = document.getElementById("paymentModalTable");
const paymentModalTotal = document.getElementById("paymentModalTotal");
const paymentModalCancel = document.getElementById("paymentModalCancel");
const paymentModalConfirm = document.getElementById("paymentModalConfirm");

let currentFilter = "all";
let orders = [];
let soundEnabled = false;
let audioCtx = null;
let seenOrderIds = null; // null until the first fetch has been processed
const FLASH_DURATION_MS = 5400;
const flashUntil = new Map(); // orderId -> timestamp when the flash should stop

// Ödeme bekleyen sipariş bilgisi
let pendingCloseOrderId = null;

/* --------------------------------------------------------------------------
   Ses bildirimi
   -------------------------------------------------------------------------- */
soundToggle.addEventListener("click", () => {
  try {
    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    soundEnabled = true;
    soundToggle.classList.add("is-enabled");
  } catch {
    soundEnabled = false;
  }
});

function playAlertTone() {
  if (!soundEnabled || !audioCtx) return;
  audioCtx.resume();
  [880, 1046.5].forEach((freq, i) => {
    const oscillator = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    oscillator.type = "sine";
    oscillator.frequency.value = freq;
    gain.gain.setValueAtTime(0.0001, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.2, audioCtx.currentTime + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.25);
    oscillator.connect(gain).connect(audioCtx.destination);
    const start = audioCtx.currentTime + i * 0.18;
    oscillator.start(start);
    oscillator.stop(start + 0.25);
  });
}

/* --------------------------------------------------------------------------
   Sabit etiket ve aksiyon haritaları
   -------------------------------------------------------------------------- */
const STATUS_LABEL = {
  new: "Yeni",
  preparing: "Hazırlanıyor",
  ready: "Hazır",
  delivered: "Teslim Edildi",
};

const NEXT_ACTION = {
  new: { status: "preparing", label: "Hazırla" },
  preparing: { status: "ready", label: "Hazır" },
  ready: { status: "delivered", label: "Teslim" },
};

/* --------------------------------------------------------------------------
   Filtre butonları
   -------------------------------------------------------------------------- */
filters.forEach((btn) => {
  btn.addEventListener("click", () => {
    currentFilter = btn.dataset.filter;
    filters.forEach((f) => f.classList.remove("is-active"));
    btn.classList.add("is-active");
    render();
  });
});

const apiFetch = bntApiFetch;

/* --------------------------------------------------------------------------
   API: Siparişleri çek
   -------------------------------------------------------------------------- */
async function fetchOrders() {
  try {
    const res = await apiFetch("/api/orders");
    if (res.status === 401 || res.status === 403) {
      lastRenderKey = "";
      emptyState.textContent = "Siparişleri görmek için giriş yapın. (Sayfayı yenileyin)";
      emptyState.hidden = false;
      return;
    }
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
      newIds.forEach((id) => seenOrderIds.add(id));
    }

    render();
  } catch {
    lastRenderKey = "";
    emptyState.textContent = "Sunucuya bağlanılamadı.";
    emptyState.hidden = false;
  }
}

/* --------------------------------------------------------------------------
   API: Garson çağrıları (Laravel; node server.js'de çağrılar sipariş olarak gelir)
   -------------------------------------------------------------------------- */
let waiterCalls = [];
let seenCallIds = null;

async function fetchWaiterCalls() {
  try {
    const res = await apiFetch("/api/waiter-calls");
    if (!res.ok) return;
    waiterCalls = await res.json();

    const currentIds = waiterCalls.map((c) => `call_${c.id}`);
    if (seenCallIds === null) {
      seenCallIds = new Set(currentIds);
    } else {
      const newIds = currentIds.filter((id) => !seenCallIds.has(id));
      if (newIds.length) {
        playAlertTone();
        newIds.forEach((id) => flashUntil.set(id, Date.now() + FLASH_DURATION_MS));
      }
      newIds.forEach((id) => seenCallIds.add(id));
    }

    render();
  } catch {
    // Çağrı listesi alınamazsa sipariş panosu çalışmaya devam eder.
  }
}

function buildCallCard(call) {
  const card = document.createElement("article");
  card.className = "order is-new";
  card.dataset.callId = call.id;
  card.innerHTML = `
    <div class="order__top">
      <h2 class="order__table">Masa ${escapeHtml(call.table)}</h2>
      <span class="order__time">${formatTime(call.createdAt)}</span>
    </div>
    <span class="order__status">${call.type === "bill" ? "Hesap İsteği" : "Garson Çağrısı"}</span>
    ${call.reason ? `<ul class="order__items"><li><span>${escapeHtml(call.reason)}</span></li></ul>` : ""}
    <div class="order__actions">
      <button type="button" class="is-primary" data-action="resolve-call">Tamam</button>
    </div>
  `;

  const flashExpiry = flashUntil.get(`call_${call.id}`);
  if (flashExpiry) {
    if (flashExpiry > Date.now()) card.classList.add("is-flash");
    else flashUntil.delete(`call_${call.id}`);
  }
  return card;
}

/* --------------------------------------------------------------------------
   Yardımcı: Sipariş toplam tutarını hesapla
   -------------------------------------------------------------------------- */
function calcOrderTotal(order) {
  return order.items.reduce((sum, item) => {
    return sum + (Number(item.price) || 0) * (Number(item.qty) || 1);
  }, 0);
}

/* --------------------------------------------------------------------------
   Render: Sipariş kartlarını oluştur
   -------------------------------------------------------------------------- */
// Polling runs every 2s; rebuilding identical cards would swallow taps on their buttons.
let lastRenderKey = "";

function render() {
  const flashing = [...flashUntil.entries()].filter(([, until]) => until > Date.now()).map(([id]) => id);
  const renderKey = JSON.stringify([currentFilter, orders, waiterCalls, flashing]);
  if (renderKey === lastRenderKey) return;
  lastRenderKey = renderKey;

  const visible =
    currentFilter === "all"
      ? orders
      : orders.filter((o) => o.status === currentFilter);

  orderCount.textContent = `${orders.length} aktif`;

  board.querySelectorAll(".order").forEach((el) => el.remove());

  const visibleCalls = currentFilter === "all" || currentFilter === "new" ? waiterCalls : [];

  if (!visible.length && !visibleCalls.length) {
    emptyState.hidden = false;
    emptyState.textContent = "Henüz sipariş yok.";
    return;
  }

  emptyState.hidden = true;

  visibleCalls.forEach((call) => board.appendChild(buildCallCard(call)));

  visible.forEach((order) => {
    const card = document.createElement("article");
    card.className = `order is-${order.status}`;
    card.dataset.id = order.id;

    const itemsHtml = order.items
      .map(
        (item) => `
        <li class="${item.isNew ? "is-new-item" : ""}">
          <span>
            ${escapeHtml(item.name)}
            ${item.isNew ? `<span class="order-item-badge-new">YENİ</span>` : ""}
          </span>
          <span class="order__qty">×${item.qty}</span>
        </li>`
      )
      .join("");

    const next = NEXT_ACTION[order.status];
    const time = formatTime(order.createdAt);
    const total = calcOrderTotal(order);

    card.innerHTML = `
      <div class="order__top">
        <h2 class="order__table">
          Masa ${escapeHtml(order.table)}
          ${order.hasNewItems ? `<span class="order-badge-has-new">Yeni Sipariş</span>` : ""}
        </h2>
        <span class="order__time">${time}</span>
      </div>
      <span class="order__status">${STATUS_LABEL[order.status] || order.status}</span>
      <ul class="order__items">${itemsHtml}</ul>
      ${order.note ? `<p class="order__note">${escapeHtml(order.note)}</p>` : ""}
      <div class="order__total">
        <span class="order__total-label">Toplam</span>
        <span class="order__total-amount">${total} ₺</span>
      </div>
      <div class="order__actions">
        ${next ? `<button type="button" class="is-primary" data-status="${next.status}">${next.label}</button>` : ""}
        ${order.status !== "done" ? `<button type="button" data-action="close-with-payment" class="${order.status === 'delivered' ? 'is-primary' : ''}">Kapat</button>` : ""}
      </div>
    `;

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

/* --------------------------------------------------------------------------
   Board tıklama olayları
   -------------------------------------------------------------------------- */
board.addEventListener("click", async (e) => {
  // Garson çağrısı: Tamam
  const resolveBtn = e.target.closest('[data-action="resolve-call"]');
  if (resolveBtn) {
    const id = resolveBtn.closest(".order")?.dataset.callId;
    if (!id) return;
    resolveBtn.disabled = true;
    try {
      const res = await apiFetch(`/api/waiter-calls/${id}`, { method: "PATCH" });
      if (!res.ok) throw new Error("güncellenemedi");
      await fetchWaiterCalls();
    } catch {
      resolveBtn.disabled = false;
    }
    return;
  }

  // Durum değiştirme butonları: Hazırla / Hazır / Teslim
  const statusBtn = e.target.closest("button[data-status]");
  if (statusBtn) {
    const card = statusBtn.closest(".order");
    const id = card?.dataset.id;
    const status = statusBtn.dataset.status;
    if (!id) return;

    statusBtn.disabled = true;
    try {
      const res = await apiFetch(`/api/orders/${id}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ status }),
      });
      if (!res.ok) throw new Error("güncellenemedi");
      await fetchOrders();
    } catch {
      statusBtn.disabled = false;
    }
    return;
  }

  // Kapat butonu → önce ödeme onayı modalını aç
  const closeBtn = e.target.closest('[data-action="close-with-payment"]');
  if (closeBtn) {
    const card = closeBtn.closest(".order");
    const id = card?.dataset.id;
    if (!id) return;

    const order = orders.find((o) => o.id === id);
    if (!order) return;

    const total = calcOrderTotal(order);
    pendingCloseOrderId = id;

    paymentModalTable.textContent = `Masa ${order.table}`;
    paymentModalTotal.textContent = `${total} ₺`;
    paymentModal.hidden = false;
  }
});

/* --------------------------------------------------------------------------
   Ödeme Onayı Modalı
   -------------------------------------------------------------------------- */
function closePaymentModal() {
  paymentModal.hidden = true;
  pendingCloseOrderId = null;
}

if (paymentModalCancel) paymentModalCancel.addEventListener("click", closePaymentModal);
if (paymentModalBackdrop) paymentModalBackdrop.addEventListener("click", closePaymentModal);

if (paymentModalConfirm) {
  paymentModalConfirm.addEventListener("click", async () => {
    if (!pendingCloseOrderId) return;

    const id = pendingCloseOrderId;
    paymentModalConfirm.disabled = true;
    paymentModalConfirm.textContent = "Kapatılıyor...";

    try {
      const res = await apiFetch(`/api/orders/${id}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ status: "done" }),
      });
      if (!res.ok) throw new Error("güncellenemedi");
      closePaymentModal();
      await fetchOrders();
    } catch {
      paymentModalConfirm.textContent = "Hata! Tekrar Dene";
    } finally {
      paymentModalConfirm.disabled = false;
      paymentModalConfirm.textContent = "✓ Ödeme Alındı, Kapat";
    }
  });
}

/* --------------------------------------------------------------------------
   Yardımcı fonksiyonlar
   -------------------------------------------------------------------------- */
function formatTime(iso) {
  try {
    return new Date(iso).toLocaleTimeString("tr-TR", {
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return "";
  }
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

/* --------------------------------------------------------------------------
   Başlat
   -------------------------------------------------------------------------- */
async function poll() {
  await fetchOrders();
  await fetchWaiterCalls();
}

poll();
setInterval(poll, 2000);
