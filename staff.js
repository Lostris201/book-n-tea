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

const STATUS_LABEL = {
  new: "Yeni",
  preparing: "Hazırlanıyor",
  ready: "Hazır",
};

const NEXT_ACTION = {
  new: { status: "preparing", label: "Hazırla" },
  preparing: { status: "ready", label: "Hazır" },
  ready: { status: "done", label: "Teslim" },
};

filters.forEach((btn) => {
  btn.addEventListener("click", () => {
    currentFilter = btn.dataset.filter;
    filters.forEach((f) => f.classList.remove("is-active"));
    btn.classList.add("is-active");
    render();
  });
});

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
      newIds.forEach((id) => seenOrderIds.add(id));
    }

    render();
  } catch {
    emptyState.textContent = "Sunucuya bağlanılamadı.";
    emptyState.hidden = false;
  }
}

function render() {
  const visible =
    currentFilter === "all"
      ? orders
      : orders.filter((o) => o.status === currentFilter);

  orderCount.textContent = `${orders.length} aktif`;

  board.querySelectorAll(".order").forEach((el) => el.remove());

  if (!visible.length) {
    emptyState.hidden = false;
    emptyState.textContent = "Henüz sipariş yok.";
    return;
  }

  emptyState.hidden = true;

  visible.forEach((order) => {
    const card = document.createElement("article");
    card.className = `order is-${order.status}`;
    card.dataset.id = order.id;

    const itemsHtml = order.items
      .map(
        (item) => `
        <li>
          <span>${escapeHtml(item.name)}</span>
          <span class="order__qty">×${item.qty}</span>
        </li>`
      )
      .join("");

    const next = NEXT_ACTION[order.status];
    const time = formatTime(order.createdAt);

    card.innerHTML = `
      <div class="order__top">
        <h2 class="order__table">Masa ${escapeHtml(order.table)}</h2>
        <span class="order__time">${time}</span>
      </div>
      <span class="order__status">${STATUS_LABEL[order.status] || order.status}</span>
      <ul class="order__items">${itemsHtml}</ul>
      ${
        order.note
          ? `<p class="order__note">${escapeHtml(order.note)}</p>`
          : ""
      }
      <div class="order__actions">
        ${
          next
            ? `<button type="button" class="is-primary" data-status="${next.status}">${next.label}</button>`
            : ""
        }
        ${
          order.status !== "done"
            ? `<button type="button" data-status="done">Kapat</button>`
            : ""
        }
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

board.addEventListener("click", async (e) => {
  const btn = e.target.closest("button[data-status]");
  if (!btn) return;

  const card = btn.closest(".order");
  const id = card?.dataset.id;
  const status = btn.dataset.status;
  if (!id) return;

  btn.disabled = true;

  try {
    const res = await fetch(`/api/orders/${id}`, {
      method: "PATCH",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ status }),
    });
    if (!res.ok) throw new Error("güncellenemedi");
    await fetchOrders();
  } catch {
    btn.disabled = false;
  }
});

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

fetchOrders();
setInterval(fetchOrders, 2000);
