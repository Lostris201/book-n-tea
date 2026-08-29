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
