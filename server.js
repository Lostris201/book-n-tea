const express = require("express");
const fs = require("fs");
const path = require("path");

const app = express();
const PORT = process.env.PORT || 3000;
const ORDERS_FILE = process.env.VERCEL ? "/tmp/orders.json" : path.join(__dirname, "orders.json");
const MENU_FILE = process.env.VERCEL ? "/tmp/menu.json" : path.join(__dirname, "menu.json");

let ordersMemory = [];
let menuMemory = null;

function readOrders() {
  try {
    if (fs.existsSync(ORDERS_FILE)) {
      const raw = fs.readFileSync(ORDERS_FILE, "utf8");
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed)) return parsed;
    }
  } catch (e) {}
  return ordersMemory;
}

function writeOrders(orders) {
  ordersMemory = orders;
  try {
    fs.writeFileSync(ORDERS_FILE, JSON.stringify(orders, null, 2), "utf8");
  } catch (e) {}
}

function readMenu() {
  if (menuMemory) return menuMemory;
  try {
    if (fs.existsSync(MENU_FILE)) {
      const raw = fs.readFileSync(MENU_FILE, "utf8");
      menuMemory = JSON.parse(raw);
      return menuMemory;
    }
  } catch (e) {}
  return null;
}

function writeMenu(data) {
  menuMemory = data;
  try {
    fs.writeFileSync(MENU_FILE, JSON.stringify(data, null, 2), "utf8");
  } catch (e) {}
}

app.use(express.json({ limit: "5mb" }));

// CORS headers for local/cross-origin/mobile requests
app.use((req, res, next) => {
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "GET, POST, PATCH, DELETE, OPTIONS");
  res.setHeader("Access-Control-Allow-Headers", "Content-Type, Authorization");
  if (req.method === "OPTIONS") {
    return res.sendStatus(200);
  }
  next();
});

// Menü API (Admin <-> Mobil QR Menü Senkronizasyonu)
app.get("/api/menu", (req, res) => {
  const menu = readMenu();
  res.json(menu || { products: [] });
});

app.post("/api/menu", (req, res) => {
  const data = req.body;
  if (!data || !Array.isArray(data.products)) {
    return res.status(400).json({ error: "Geçersiz menü verisi." });
  }
  writeMenu(data);
  res.json({ success: true, count: data.products.length });
});

app.get("/api/orders", (req, res) => {
  const status = req.query.status;
  let orders = readOrders();
  if (status) {
    orders = orders.filter((o) => o.status === status);
  } else {
    orders = orders.filter((o) => o.status !== "done");
  }
  orders.sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt));
  res.json(orders);
});

app.post("/api/orders", (req, res) => {
  const { table, items, note } = req.body || {};

  if (!table || !Array.isArray(items) || items.length === 0) {
    return res.status(400).json({ error: "Masa ve ürünler gerekli." });
  }

  const cleanItems = items
    .map((item) => ({
      name: String(item.name || "").trim(),
      price: Number(item.price) || 0,
      qty: Math.max(1, Number(item.qty) || 1),
    }))
    .filter((item) => item.name);

  if (!cleanItems.length) {
    return res.status(400).json({ error: "Geçerli ürün yok." });
  }

  const orders = readOrders();
  const tableStr = String(table).trim();

  // Aynı masanın aktif (done olmayan) siparişini bul
  const existingIdx = orders.findIndex(
    (o) => String(o.table).trim() === tableStr && o.status !== "done"
  );

  if (existingIdx !== -1) {
    // Mevcut siparişe yeni ürünleri ekle
    const existing = orders[existingIdx];
    cleanItems.forEach((newItem) => {
      // Aynı isimde ürün varsa miktarını artır
      const sameIdx = existing.items.findIndex((i) => i.name === newItem.name);
      if (sameIdx !== -1) {
        existing.items[sameIdx].qty += newItem.qty;
      } else {
        existing.items.push(newItem);
      }
    });
    // Not varsa ekle / güncelle
    if (note && String(note).trim()) {
      const newNote = String(note).trim().slice(0, 200);
      existing.note = existing.note
        ? `${existing.note} | ${newNote}`
        : newNote;
    }
    existing.status = "new"; // Yeni ürün geldi bildirimi
    existing.updatedAt = new Date().toISOString();
    writeOrders(orders);
    return res.status(200).json(existing);
  }

  // Aktif sipariş yok → yeni sipariş oluştur
  const order = {
    id: `ord_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`,
    table: tableStr,
    items: cleanItems,
    note: note ? String(note).trim().slice(0, 200) : "",
    status: "new",
    createdAt: new Date().toISOString(),
  };

  orders.push(order);
  writeOrders(orders);

  res.status(201).json(order);
});

app.patch("/api/orders/:id", (req, res) => {
  const { status } = req.body || {};
  const allowed = ["new", "preparing", "ready", "done"];

  if (!allowed.includes(status)) {
    return res.status(400).json({ error: "Geçersiz durum." });
  }

  const orders = readOrders();
  const index = orders.findIndex((o) => o.id === req.params.id);

  if (index === -1) {
    return res.status(404).json({ error: "Sipariş bulunamadı." });
  }

  orders[index].status = status;
  writeOrders(orders);
  res.json(orders[index]);
});

app.use(express.static(__dirname));

// Vercel ortamında değilsek sunucuyu dinle
if (process.env.NODE_ENV !== "production" && !process.env.VERCEL) {
  app.listen(PORT, () => {
    console.log(`Book n Tea → http://localhost:${PORT}`);
    console.log(`Kafe paneli → http://localhost:${PORT}/staff.html`);
  }).on("error", (err) => {
    if (err.code === "EADDRINUSE") {
      console.error(
        `Port ${PORT} dolu. Önce şu komutu çalıştırın, sonra tekrar npm start:\n` +
          `  npx --yes kill-port ${PORT}`
      );
      process.exit(1);
    }
    throw err;
  });
}

// Vercel için uygulamayı dışa aktar
module.exports = app;
