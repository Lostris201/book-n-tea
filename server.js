const express = require("express");
const crypto = require("crypto");
const { createClient } = require("@supabase/supabase-js");

const app = express();
const PORT = process.env.PORT || 3000;

const supabase = createClient(
  process.env.SUPABASE_URL,
  process.env.SUPABASE_SERVICE_KEY
);

const STAFF_COOKIE = "staff_auth";
const STAFF_SESSION_MS = 12 * 60 * 60 * 1000; // 12 hours — roughly one shift

function signStaffToken(expiresAt) {
  const hmac = crypto
    .createHmac("sha256", process.env.STAFF_AUTH_SECRET)
    .update(String(expiresAt))
    .digest("hex");
  return `${expiresAt}.${hmac}`;
}

function verifyStaffToken(token) {
  if (!token) return false;
  const [expiresAtRaw, hmac] = token.split(".");
  const expiresAt = Number(expiresAtRaw);
  if (!expiresAt || !hmac || Date.now() > expiresAt) return false;

  const expected = crypto
    .createHmac("sha256", process.env.STAFF_AUTH_SECRET)
    .update(String(expiresAt))
    .digest("hex");

  const a = Buffer.from(hmac);
  const b = Buffer.from(expected);
  return a.length === b.length && crypto.timingSafeEqual(a, b);
}

function safeEquals(a, b) {
  const bufA = crypto.createHash("sha256").update(String(a)).digest();
  const bufB = crypto.createHash("sha256").update(String(b)).digest();
  return crypto.timingSafeEqual(bufA, bufB);
}

function getCookie(req, name) {
  const header = req.headers.cookie;
  if (!header) return null;
  const match = header
    .split(";")
    .map((part) => part.trim())
    .find((part) => part.startsWith(`${name}=`));
  return match ? decodeURIComponent(match.slice(name.length + 1)) : null;
}

function requireStaffAuth(req, res, next) {
  const token = getCookie(req, STAFF_COOKIE);
  if (!verifyStaffToken(token)) {
    return res.status(401).json({ error: "Giriş gerekli." });
  }
  next();
}

app.use(express.json());

app.get("/api/staff/session", requireStaffAuth, (req, res) => {
  res.json({ ok: true });
});

app.post("/api/staff/login", (req, res) => {
  const { password } = req.body || {};

  if (!password || !safeEquals(password, process.env.STAFF_PASSWORD)) {
    return res.status(401).json({ error: "Şifre hatalı." });
  }

  const expiresAt = Date.now() + STAFF_SESSION_MS;
  const token = signStaffToken(expiresAt);
  const secure = process.env.VERCEL ? "; Secure" : "";

  res.setHeader(
    "Set-Cookie",
    `${STAFF_COOKIE}=${encodeURIComponent(token)}; HttpOnly; SameSite=Strict; Path=/; Max-Age=${
      STAFF_SESSION_MS / 1000
    }${secure}`
  );
  res.json({ ok: true });
});

app.get("/api/orders", requireStaffAuth, async (req, res) => {
  const status = req.query.status;

  let query = supabase.from("orders").select("*").order("created_at", { ascending: true });
  query = status ? query.eq("status", status) : query.neq("status", "done");

  const { data, error } = await query;
  if (error) {
    return res.status(500).json({ error: "Siparişler alınamadı." });
  }

  res.json(data.map(toClientOrder));
});

app.post("/api/orders", async (req, res) => {
  const { table, items, note } = req.body || {};
  const tableNumber = Number.parseInt(String(table ?? "").trim(), 10);

  if (
    !Number.isInteger(tableNumber) ||
    tableNumber <= 0 ||
    !Array.isArray(items) ||
    items.length === 0
  ) {
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

  const row = {
    id: `ord_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`,
    table_number: tableNumber,
    items: cleanItems,
    note: note ? String(note).trim().slice(0, 200) : "",
    status: "new",
  };

  const { data, error } = await supabase.from("orders").insert(row).select().single();
  if (error) {
    return res.status(500).json({ error: "Sipariş kaydedilemedi." });
  }

  res.status(201).json(toClientOrder(data));
});

app.patch("/api/orders/:id", requireStaffAuth, async (req, res) => {
  const { status } = req.body || {};
  const allowed = ["new", "preparing", "ready", "done"];

  if (!allowed.includes(status)) {
    return res.status(400).json({ error: "Geçersiz durum." });
  }

  const { data, error } = await supabase
    .from("orders")
    .update({ status, updated_at: new Date().toISOString() })
    .eq("id", req.params.id)
    .select()
    .single();

  if (error || !data) {
    return res.status(404).json({ error: "Sipariş bulunamadı." });
  }

  res.json(toClientOrder(data));
});

// Supabase rows -> the shape script.js/staff.js already expect.
function toClientOrder(row) {
  return {
    id: row.id,
    table: String(row.table_number),
    items: row.items,
    note: row.note,
    status: row.status,
    createdAt: row.created_at,
  };
}

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
