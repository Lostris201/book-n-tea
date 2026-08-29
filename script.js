const cart = new Map();

const loader = document.getElementById("loader");
const page = document.getElementById("page");
const cartFab = document.getElementById("cartFab");
const cartCount = document.getElementById("cartCount");
const cartSheet = document.getElementById("cartSheet");
const cartList = document.getElementById("cartList");
const cartTotal = document.getElementById("cartTotal");
const cartMsg = document.getElementById("cartMsg");
const orderNote = document.getElementById("orderNote");
const submitOrder = document.getElementById("submitOrder");
const toast = document.getElementById("toast");

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

document.querySelectorAll(".cat").forEach((btn) => {
  btn.addEventListener("click", () => {
    const target = btn.dataset.cat;
    document.querySelectorAll(".cat").forEach((b) => b.classList.remove("is-active"));
    btn.classList.add("is-active");
    document.querySelectorAll(".menu-panel").forEach((panel) => {
      const match = panel.dataset.panel === target;
      panel.classList.toggle("is-visible", match);
      panel.hidden = !match;
    });
  });
});

document.querySelectorAll(".item__add").forEach((btn) => {
  btn.addEventListener("click", () => {
    const item = btn.closest(".item");
    const name = item.dataset.name;
    const price = Number(item.dataset.price);
    const existing = cart.get(name);

    if (existing) {
      existing.qty += 1;
    } else {
      cart.set(name, { name, price, qty: 1 });
    }

    renderCart();
    showToast(`${name} eklendi`);
  });
});

function cartQty() {
  let total = 0;
  cart.forEach((item) => {
    total += item.qty;
  });
  return total;
}

function cartSum() {
  let total = 0;
  cart.forEach((item) => {
    total += item.price * item.qty;
  });
  return total;
}

function renderCart() {
  const qty = cartQty();
  cartCount.textContent = String(qty);
  cartFab.hidden = qty === 0;

  cartList.innerHTML = "";
  cart.forEach((item) => {
    const li = document.createElement("li");
    li.className = "cart-item";
    li.innerHTML = `
      <span class="cart-item__name">${escapeHtml(item.name)}</span>
      <div class="cart-item__qty">
        <button type="button" data-act="dec" data-name="${escapeAttr(item.name)}" aria-label="Azalt">−</button>
        <span>${item.qty}</span>
        <button type="button" data-act="inc" data-name="${escapeAttr(item.name)}" aria-label="Artır">+</button>
      </div>
      <span class="cart-item__price">${item.price * item.qty} ₺</span>
    `;
    cartList.appendChild(li);
  });

  cartTotal.textContent = `${cartSum()} ₺`;
}

cartList.addEventListener("click", (e) => {
  const btn = e.target.closest("button[data-act]");
  if (!btn) return;

  const name = btn.dataset.name;
  const item = cart.get(name);
  if (!item) return;

  if (btn.dataset.act === "inc") {
    item.qty += 1;
  } else {
    item.qty -= 1;
    if (item.qty <= 0) cart.delete(name);
  }

  renderCart();
  if (cart.size === 0) closeCart();
});

cartFab.addEventListener("click", () => {
  cartMsg.textContent = "";
  cartMsg.className = "cart-msg";
  cartSheet.hidden = false;
});

cartSheet.addEventListener("click", (e) => {
  if (e.target.matches("[data-close-cart]")) closeCart();
});

function closeCart() {
  cartSheet.hidden = true;
}

submitOrder.addEventListener("click", async () => {
  if (cart.size === 0) {
    cartMsg.textContent = "Sepet boş.";
    cartMsg.className = "cart-msg is-error";
    return;
  }

  const items = [...cart.values()];
  submitOrder.disabled = true;
  cartMsg.textContent = "Gönderiliyor…";
  cartMsg.className = "cart-msg";

  try {
    const res = await fetch("/api/orders", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        table: tableNumber,
        items,
        note: orderNote.value.trim(),
      }),
    });

    if (!res.ok) {
      const data = await res.json().catch(() => ({}));
      throw new Error(data.error || "Sipariş gönderilemedi.");
    }

    cart.clear();
    orderNote.value = "";
    renderCart();
    cartMsg.textContent = "Sipariş kafeye iletildi.";
    cartMsg.className = "cart-msg is-ok";
    showToast("Sipariş alındı");

    setTimeout(() => {
      closeCart();
      cartMsg.textContent = "";
    }, 1200);
  } catch (err) {
    cartMsg.textContent =
      err.message || "Bağlantı hatası. Sunucunun açık olduğundan emin olun.";
    cartMsg.className = "cart-msg is-error";
  } finally {
    submitOrder.disabled = false;
  }
});

function showToast(message) {
  toast.textContent = message;
  toast.hidden = false;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toast.hidden = true;
  }, 1600);
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function escapeAttr(str) {
  return escapeHtml(str).replace(/'/g, "&#39;");
}
