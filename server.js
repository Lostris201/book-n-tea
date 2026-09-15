const express = require("express");
const fs = require("fs");
const path = require("path");

const app = express();
const PORT = process.env.PORT || 3000;
let ordersMemory = [];

function readOrders() {
  return ordersMemory;
}

function writeOrders(orders) {
  ordersMemory = orders;
}

app.use(express.json());

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
