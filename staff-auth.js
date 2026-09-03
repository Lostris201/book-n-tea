// Shared staff login gate, used by both staff.js and qr.js.
// Exposes `ensureStaffAuth()`, which resolves once a valid staff session
// cookie is present — showing a password overlay first if it isn't.

let pendingGate = null;

function ensureStaffAuth() {
  return fetch("/api/staff/session").then((res) => {
    if (res.ok) return true;
    return showStaffLoginGate();
  });
}

function showStaffLoginGate() {
  if (pendingGate) return pendingGate;

  const overlay = document.createElement("div");
  overlay.className = "staff-auth-gate";
  overlay.innerHTML = `
    <form class="staff-auth-gate__card" role="dialog" aria-label="Kafe paneli girişi">
      <p class="staff-auth-gate__eyebrow">Book n Tea</p>
      <h1 class="staff-auth-gate__title">Kafe Paneli</h1>
      <label class="staff-auth-gate__label" for="staffAuthPassword">Şifre</label>
      <input
        id="staffAuthPassword"
        class="staff-auth-gate__input"
        type="password"
        name="password"
        autocomplete="current-password"
        required
      />
      <p class="staff-auth-gate__error" role="alert" hidden>Şifre hatalı.</p>
      <button class="staff-auth-gate__submit" type="submit">Giriş yap</button>
    </form>
  `;
  document.body.appendChild(overlay);

  const input = overlay.querySelector("input");
  const errorEl = overlay.querySelector(".staff-auth-gate__error");
  input.focus();

  pendingGate = new Promise((resolve) => {
    overlay.querySelector("form").addEventListener("submit", async (e) => {
      e.preventDefault();
      errorEl.hidden = true;

      try {
        const res = await fetch("/api/staff/login", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ password: input.value }),
        });

        if (!res.ok) {
          errorEl.hidden = false;
          input.select();
          return;
        }

        overlay.remove();
        pendingGate = null;
        resolve(true);
      } catch {
        errorEl.hidden = false;
      }
    });
  });

  return pendingGate;
}
