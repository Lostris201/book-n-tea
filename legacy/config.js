/* ==========================================================================
   BOOK & TEA HOUSE — API ayarı (geçiş dönemi)
   Boş bırakılırsa aynı sunucu kullanılır (node server.js).
   Laravel'e bağlamak için: window.BNT_API_BASE = "http://localhost:8000";
   ========================================================================== */
window.BNT_API_BASE = window.BNT_API_BASE || "";

window.bntApiUrl = function (path) {
  return String(window.BNT_API_BASE || "").replace(/\/+$/, "") + path;
};

/* Laravel personel/yönetici oturum anahtarı (node server.js bunu yok sayar). */
const BNT_TOKEN_KEY = "bnt_api_token";

window.bntGetToken = function () {
  try {
    return localStorage.getItem(BNT_TOKEN_KEY) || "";
  } catch {
    return "";
  }
};

window.bntSetToken = function (token) {
  try {
    if (token) localStorage.setItem(BNT_TOKEN_KEY, token);
    else localStorage.removeItem(BNT_TOKEN_KEY);
  } catch {}
};

window.bntAuthHeaders = function () {
  const token = window.bntGetToken();
  return token ? { Authorization: `Bearer ${token}` } : {};
};

/* 401 gelirse e-posta/şifre sorup oturum açar. Başarılıysa true döner. */
let bntLoginPromise = null;
window.bntLogin = function () {
  if (bntLoginPromise) return bntLoginPromise;
  bntLoginPromise = (async () => {
    try {
      const email = window.prompt("Personel girişi — e-posta:");
      if (!email) return false;
      const password = window.prompt("Şifre:");
      if (!password) return false;

      const res = await fetch(window.bntApiUrl("/api/auth/login"), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.token) {
        window.alert(data.error || "Giriş yapılamadı.");
        return false;
      }
      window.bntSetToken(data.token);
      return true;
    } catch {
      return false;
    } finally {
      bntLoginPromise = null;
    }
  })();
  return bntLoginPromise;
};
