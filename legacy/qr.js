const TABLE_COUNT = 20;
const grid = document.getElementById("qrGrid");

// Laravel: QR codes carry each table's secret token (?t=), fetched with an admin/manager login.
// node server.js: legacy ?masa=N codes.
if (window.BNT_API_BASE) {
  loadTokenCards();
} else {
  for (let table = 1; table <= TABLE_COUNT; table += 1) {
    grid.appendChild(buildCard(`Masa ${table}`, `${window.location.origin}/index.html?masa=${table}`));
  }
}

async function loadTokenCards() {
  try {
    const res = await bntApiFetch("/api/tables");
    if (!res.ok) {
      const data = await res.json().catch(() => ({}));
      throw new Error(data.error || "Masalar alınamadı.");
    }
    const tables = await res.json();
    tables
      .filter((t) => t.isActive)
      .forEach((t) => {
        const url = `${window.location.origin}/index.html?t=${encodeURIComponent(t.qrToken)}`;
        grid.appendChild(buildCard(t.name, url));
      });
  } catch (err) {
    const msg = document.createElement("p");
    msg.className = "qr-header__hint";
    msg.textContent = err.message || "Masalar alınamadı.";
    grid.appendChild(msg);
  }
}

function buildCard(label, url) {
  const qr = qrcode(0, "M");
  qr.addData(url);
  qr.make();

  const card = document.createElement("article");
  card.className = "qr-card";
  card.innerHTML = `
    <p class="qr-card__number">${escapeHtml(label)}</p>
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
