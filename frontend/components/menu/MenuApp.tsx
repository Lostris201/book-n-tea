"use client";

import { useSearchParams } from "next/navigation";
import { useEffect, useMemo, useState } from "react";
import { API_URL, ApiError, api } from "@/lib/api";
import { useCart } from "@/lib/cart";
import { formatCents, toCents } from "@/lib/money";
import type { Menu, MenuProduct, Order } from "@/lib/types";
import CartDrawer from "./CartDrawer";
import CustomizeModal from "./CustomizeModal";
import Hero from "./Hero";
import Loader from "./Loader";
import ProductCard, { tagLabels } from "./ProductCard";
import ReceiptModal from "./ReceiptModal";
import WaiterModal from "./WaiterModal";

type TableState =
  | { status: "loading" }
  | { status: "missing" }
  | { status: "invalid"; message: string }
  | { status: "valid"; number: number; name: string };

const TAGS = [
  { id: "all", label: "Tüm Lezzetler" },
  { id: "bestseller", label: "⭐ Bestseller" },
  { id: "vegan", label: "🌱 Vegan" },
  { id: "glutenfree", label: "🌾 Gluten-Free" },
  { id: "hot", label: "🔥 Sıcak" },
  { id: "cold", label: "❄️ Soğuk" },
];

const MIN_LOADER_MS = 1000;

export default function MenuApp() {
  const token = useSearchParams().get("t");

  const [table, setTable] = useState<TableState>(token ? { status: "loading" } : { status: "missing" });
  const [menu, setMenu] = useState<Menu | null>(null);
  const [menuError, setMenuError] = useState<string | null>(null);
  const [minDelayPassed, setMinDelayPassed] = useState(false);
  const [browseOnly, setBrowseOnly] = useState(false);

  const [category, setCategory] = useState("all");
  const [tag, setTag] = useState("all");
  const [query, setQuery] = useState("");

  const [customizing, setCustomizing] = useState<MenuProduct | null>(null);
  const [cartOpen, setCartOpen] = useState(false);
  const [waiterOpen, setWaiterOpen] = useState(false);
  const [receipt, setReceipt] = useState<Order | null>(null);
  const [note, setNote] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [cartError, setCartError] = useState<string | null>(null);

  const cart = useCart(table.status === "valid" ? token : null);

  useEffect(() => {
    const timer = setTimeout(() => setMinDelayPassed(true), MIN_LOADER_MS);
    return () => clearTimeout(timer);
  }, []);

  // Menu is always fetched at runtime so admin changes show up without a redeploy.
  useEffect(() => {
    const controller = new AbortController();
    api
      .menu(controller.signal)
      .then((data) => {
        setMenu({ ...data, categories: [...data.categories].sort((a, b) => (a.order || 0) - (b.order || 0)) });
      })
      .catch((err) => {
        if (err instanceof DOMException && err.name === "AbortError") return;
        setMenuError(err instanceof ApiError ? err.message : "Menü yüklenemedi.");
      });
    return () => controller.abort();
  }, []);

  useEffect(() => {
    if (!token) return;
    let cancelled = false;
    api
      .resolveTable(token)
      .then((t) => !cancelled && setTable({ status: "valid", number: t.number, name: t.name }))
      .catch((err) => {
        if (cancelled) return;
        const message =
          err instanceof ApiError && err.status === 404
            ? "Bu QR kod geçersiz ya da yenilenmiş. Lütfen masanızdaki QR kodu tekrar okutun."
            : err instanceof ApiError
              ? err.message
              : "Masa bilgisi alınamadı.";
        setTable({ status: "invalid", message });
      });
    return () => {
      cancelled = true;
    };
  }, [token]);

  const canOrder = table.status === "valid";
  const ready = minDelayPassed && (menu !== null || menuError !== null) && table.status !== "loading";
  const tableLabel = table.status === "valid" ? `Masa ${String(table.number).padStart(2, "0")}` : "Masa —";

  const settings = menu?.settings;
  const allowWaiter = settings?.callWaiter !== false;
  const allowBill = settings?.requestBill !== false;

  const filtered = useMemo(() => {
    if (!menu) return [];
    const q = query.trim().toLocaleLowerCase("tr-TR");
    return menu.products.filter((p) => {
      const catMatch = category === "all" || p.category === category;
      // The API has no dietary tags yet; those chips match nothing (same as the legacy menu with server data).
      const tagMatch = tag === "all" || (tag === "bestseller" && p.bestseller);
      const labels = tagLabels(p);
      const textMatch =
        !q ||
        p.name.toLocaleLowerCase("tr-TR").includes(q) ||
        p.desc.toLocaleLowerCase("tr-TR").includes(q) ||
        labels.some((l) => l.toLocaleLowerCase("tr-TR").includes(q));
      return catMatch && tagMatch && textMatch;
    });
  }, [menu, category, tag, query]);

  const addProduct = (product: MenuProduct) => {
    if (product.customizable) {
      setCustomizing(product);
      return;
    }
    cart.add({ productId: product.id, name: product.name, optionIds: [], optionsLabel: "", unitCents: toCents(product.price), qty: 1 });
  };

  const submitOrder = async () => {
    if (!token || !canOrder) return;
    if (cart.lines.length === 0) {
      setCartError("Sepetinizde ürün bulunmuyor.");
      return;
    }
    setSubmitting(true);
    setCartError(null);
    try {
      const order = await api.placeOrder({
        table_token: token,
        items: cart.lines.map((l) => ({ product_id: l.productId, qty: l.qty, option_ids: l.optionIds })),
        note: note.trim(),
      });
      setReceipt(order);
      cart.clear();
      setNote("");
      setCartOpen(false);
    } catch (err) {
      if (err instanceof ApiError && err.status === 404) {
        setTable({ status: "invalid", message: err.message });
      }
      setCartError(err instanceof ApiError ? err.message : "Bağlantı hatası. Lütfen tekrar deneyin.");
    } finally {
      setSubmitting(false);
    }
  };

  const callWaiter = (type: "waiter" | "bill", reason: string) => {
    setWaiterOpen(false);
    if (!token) return;
    // Fire and forget, like the legacy menu; staff see it on their board.
    api.callWaiter({ table_token: token, type, reason }).catch(() => {});
  };

  const showNoTable = ready && !canOrder && !browseOnly;

  return (
    <>
      <Loader done={ready} />

      <div className="app-shell">
        <Hero
          tableLabel={tableLabel}
          showWaiterButton={canOrder && (allowWaiter || allowBill)}
          onCallWaiter={() => setWaiterOpen(true)}
        />

        {showNoTable ? (
          <main className="menu-container">
            <div className="empty-search">
              <div className="empty-search__icon">📷</div>
              <h3 className="empty-search__title">Masanızdaki QR Kodu Okutun</h3>
              <p className="empty-search__desc">
                {table.status === "invalid"
                  ? table.message
                  : "Sipariş verebilmek için masanızdaki QR kodu telefonunuzun kamerasıyla okutun."}
              </p>
              <button type="button" className="btn-secondary" onClick={() => setBrowseOnly(true)}>
                Menüye Göz At
              </button>
            </div>
          </main>
        ) : (
          <>
            <section className="search-section">
              <div className="search-box">
                <svg className="search-box__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <circle cx="11" cy="11" r="8" />
                  <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input
                  type="text"
                  className="search-box__input"
                  placeholder="Lezzet veya içerik arayın... (ör. Earl Grey, Latte, Cheesecake)"
                  aria-label="Menüde Ara"
                  value={query}
                  onChange={(e) => setQuery(e.target.value)}
                />
                {query && (
                  <button type="button" className="search-box__clear" aria-label="Aramayı Temizle" onClick={() => setQuery("")}>
                    ✕
                  </button>
                )}
              </div>

              <div className="tag-chips">
                {TAGS.map((t) => (
                  <button key={t.id} className={`tag-chip${tag === t.id ? " is-active" : ""}`} onClick={() => setTag(t.id)}>
                    {t.label}
                  </button>
                ))}
              </div>
            </section>

            <nav className="sticky-nav" aria-label="Menü Kategorileri">
              <div className="categories-scroll">
                <button className={`cat-pill${category === "all" ? " is-active" : ""}`} type="button" onClick={() => setCategory("all")}>
                  <span className="cat-pill__icon">✨</span>
                  <span className="cat-pill__title">Tümü</span>
                </button>
                {menu?.categories.map((c) => (
                  <button
                    key={c.id}
                    className={`cat-pill${category === c.id ? " is-active" : ""}`}
                    type="button"
                    onClick={() => setCategory(c.id)}
                  >
                    <span className="cat-pill__icon">{c.icon || "☕"}</span>
                    <span className="cat-pill__title">{c.name}</span>
                  </button>
                ))}
              </div>
            </nav>

            <main className="menu-container">
              <div className="menu-status">
                <span>
                  {menuError
                    ? menuError
                    : filtered.length === 0
                      ? "0 lezzet bulundu"
                      : `${filtered.length} lezzet listeleniyor`}
                </span>
              </div>

              {browseOnly && !canOrder && (
                <p className="menu-status">Sipariş vermek için masanızdaki QR kodu okutun.</p>
              )}

              <div className="product-grid">
                {filtered.map((product) => (
                  <ProductCard
                    key={product.id}
                    product={product}
                    canOrder={canOrder}
                    cartQty={cart.qtyForProduct(product.id)}
                    onAdd={() => addProduct(product)}
                    onCustomize={() => setCustomizing(product)}
                    onIncrement={() => cart.changeProductQty(product.id, 1)}
                    onDecrement={() => cart.changeProductQty(product.id, -1)}
                  />
                ))}
              </div>

              {menu && filtered.length === 0 && (
                <div className="empty-search">
                  <div className="empty-search__icon">🔍</div>
                  <h3 className="empty-search__title">Aradığınız Lezzet Bulunamadı</h3>
                  <p className="empty-search__desc">Lütfen farklı bir arama terimi deneyin veya filtreleri temizleyin.</p>
                  <button
                    type="button"
                    className="btn-secondary"
                    onClick={() => {
                      setQuery("");
                      setCategory("all");
                      setTag("all");
                    }}
                  >
                    Tüm Menüyü Göster
                  </button>
                </div>
              )}
            </main>
          </>
        )}

        <footer className="app-footer">
          <div className="app-footer__crest">❧</div>
          <p className="app-footer__brand">Book &amp; Tea House</p>
          <p className="app-footer__address">
            {settings?.address || "Kütüphane Çıkmazı No: 12, İstanbul"} • {settings?.phone || "0212 555 01 99"}
          </p>
          <p className="app-footer__timing">{settings?.hours || "Açılış: Her gün 08:30 – 23:00"}</p>
          <div className="app-footer__links">
            <a href="/staff/" target="_blank" rel="noopener">
              Kafe Personel Paneli
            </a>{" "}
            •{" "}
            <a href={`${API_URL}/admin/cafe-tables/qr`} target="_blank" rel="noopener">
              Masa QR Kodları
            </a>
          </div>
        </footer>
      </div>

      {canOrder && cart.count > 0 && (
        <div className="cart-bar">
          <div className="cart-bar__inner">
            <div className="cart-bar__info">
              <div className="cart-bar__count-badge">{cart.count}</div>
              <div className="cart-bar__pricing">
                <span className="cart-bar__label">Sipariş Toplamı</span>
                <strong className="cart-bar__total">{formatCents(cart.totalCents)}</strong>
              </div>
            </div>
            <button
              className="cart-bar__action"
              type="button"
              onClick={() => {
                setCartError(null);
                setCartOpen(true);
              }}
            >
              <span>Siparişi İncele</span>
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2">
                <polyline points="9 18 15 12 9 6" />
              </svg>
            </button>
          </div>
        </div>
      )}

      {cartOpen && (
        <CartDrawer
          tableLabel={tableLabel}
          lines={cart.lines}
          totalCents={cart.totalCents}
          note={note}
          submitting={submitting}
          error={cartError}
          onNoteChange={setNote}
          onChangeQty={cart.changeQty}
          onSubmit={submitOrder}
          onClose={() => setCartOpen(false)}
        />
      )}

      {customizing && menu && (
        <CustomizeModal product={customizing} menu={menu} onClose={() => setCustomizing(null)} onAdd={cart.add} />
      )}

      {waiterOpen && (
        <WaiterModal allowWaiter={allowWaiter} allowBill={allowBill} onSelect={callWaiter} onClose={() => setWaiterOpen(false)} />
      )}

      {receipt && <ReceiptModal order={receipt} onClose={() => setReceipt(null)} />}
    </>
  );
}
