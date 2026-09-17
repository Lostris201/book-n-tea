"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { API_URL, ApiError, api } from "@/lib/api";
import { formatCents, toCents } from "@/lib/money";
import type { Order, OrderStatus, StaffUser, WaiterCall } from "@/lib/types";
import StaffHeaderBrand from "./StaffHeaderBrand";

const POLL_MS = 2000;
const FLASH_DURATION_MS = 5400;

const STATUS_LABEL: Record<string, string> = {
  new: "Yeni",
  preparing: "Hazırlanıyor",
  ready: "Hazır",
  delivered: "Teslim Edildi",
};

const NEXT_ACTION: Partial<Record<OrderStatus, { status: OrderStatus; label: string }>> = {
  new: { status: "preparing", label: "Hazırla" },
  preparing: { status: "ready", label: "Hazır" },
  ready: { status: "delivered", label: "Teslim" },
};

const FILTERS = [
  { id: "all", label: "Tümü", className: "filter" },
  { id: "new", label: "✨ Yeni", className: "filter filter--new" },
  { id: "preparing", label: "⏳ Hazırlanıyor", className: "filter filter--prep" },
  { id: "ready", label: "✓ Hazır", className: "filter filter--ready" },
  { id: "delivered", label: "🤝 Teslim Edildi", className: "filter filter--delivered" },
];

function formatTime(iso: string) {
  try {
    return new Date(iso).toLocaleTimeString("tr-TR", { hour: "2-digit", minute: "2-digit" });
  } catch {
    return "";
  }
}

type Props = {
  user: StaffUser;
  onSignedOut: (message?: string) => void;
};

export default function Board({ user, onSignedOut }: Props) {
  const [orders, setOrders] = useState<Order[]>([]);
  const [calls, setCalls] = useState<WaiterCall[]>([]);
  const [loaded, setLoaded] = useState(false);
  const [connectionError, setConnectionError] = useState(false);
  const [filter, setFilter] = useState("all");
  const [flashUntil, setFlashUntil] = useState<Record<string, number>>({});
  const [busy, setBusy] = useState<Record<string, boolean>>({});
  const [closing, setClosing] = useState<Order | null>(null);
  const [closeError, setCloseError] = useState(false);
  const [soundEnabled, setSoundEnabled] = useState(false);

  const audioCtx = useRef<AudioContext | null>(null);
  // null until the first successful fetch, so existing orders don't alert on page load.
  const seenItemCounts = useRef<Map<string, number> | null>(null);
  const seenCalls = useRef<Set<number> | null>(null);

  const playAlertTone = useCallback(() => {
    const ctx = audioCtx.current;
    if (!ctx) return;
    ctx.resume();
    [880, 1046.5].forEach((freq, i) => {
      const oscillator = ctx.createOscillator();
      const gain = ctx.createGain();
      oscillator.type = "sine";
      oscillator.frequency.value = freq;
      gain.gain.setValueAtTime(0.0001, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.2, ctx.currentTime + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.25);
      oscillator.connect(gain).connect(ctx.destination);
      const start = ctx.currentTime + i * 0.18;
      oscillator.start(start);
      oscillator.stop(start + 0.25);
    });
  }, []);

  const handleAuthError = useCallback(
    (err: unknown) => {
      if (err instanceof ApiError && (err.status === 401 || err.status === 403)) {
        onSignedOut(err.status === 403 ? err.message : "Oturumunuz sona erdi. Lütfen tekrar giriş yapın.");
        return true;
      }
      return false;
    },
    [onSignedOut],
  );

  const refresh = useCallback(async () => {
    try {
      const [nextOrders, nextCalls] = await Promise.all([api.orders(), api.waiterCalls()]);

      // Alert on genuinely new orders, on items added to an existing order, and on new waiter calls.
      const alerts: string[] = [];
      const itemCounts = new Map(nextOrders.map((o) => [o.id, o.items.length]));
      if (seenItemCounts.current) {
        for (const [id, count] of itemCounts) {
          const before = seenItemCounts.current.get(id);
          if (before === undefined || count > before) alerts.push(id);
        }
      }
      seenItemCounts.current = itemCounts;

      const callIds = new Set(nextCalls.map((c) => c.id));
      if (seenCalls.current) {
        for (const id of callIds) {
          if (!seenCalls.current.has(id)) alerts.push(`call_${id}`);
        }
      }
      seenCalls.current = callIds;

      if (alerts.length) {
        playAlertTone();
        const until = Date.now() + FLASH_DURATION_MS;
        setFlashUntil((current) => ({ ...current, ...Object.fromEntries(alerts.map((id) => [id, until])) }));
      }

      setOrders(nextOrders);
      setCalls(nextCalls);
      setConnectionError(false);
      setLoaded(true);
    } catch (err) {
      if (!handleAuthError(err)) setConnectionError(true);
    }
  }, [handleAuthError, playAlertTone]);

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- initial fetch, then poll
    refresh();
    const timer = setInterval(refresh, POLL_MS);
    return () => clearInterval(timer);
  }, [refresh]);

  // Drop expired flashes so the animation class is removed.
  useEffect(() => {
    const ids = Object.keys(flashUntil);
    if (ids.length === 0) return;
    const nextExpiry = Math.min(...Object.values(flashUntil));
    const timer = setTimeout(() => {
      const now = Date.now();
      setFlashUntil((current) => Object.fromEntries(Object.entries(current).filter(([, until]) => until > now)));
    }, Math.max(0, nextExpiry - Date.now()) + 50);
    return () => clearTimeout(timer);
  }, [flashUntil]);

  const updateStatus = async (order: Order, status: OrderStatus) => {
    setBusy((b) => ({ ...b, [order.id]: true }));
    try {
      await api.updateOrderStatus(order.id, status);
      await refresh();
    } catch (err) {
      handleAuthError(err);
    } finally {
      setBusy((b) => ({ ...b, [order.id]: false }));
    }
  };

  const resolveCall = async (call: WaiterCall) => {
    const key = `call_${call.id}`;
    setBusy((b) => ({ ...b, [key]: true }));
    try {
      await api.resolveWaiterCall(call.id);
      await refresh();
    } catch (err) {
      handleAuthError(err);
    } finally {
      setBusy((b) => ({ ...b, [key]: false }));
    }
  };

  const confirmClose = async () => {
    if (!closing) return;
    setBusy((b) => ({ ...b, payment: true }));
    setCloseError(false);
    try {
      await api.updateOrderStatus(closing.id, "done");
      setClosing(null);
      await refresh();
    } catch (err) {
      if (!handleAuthError(err)) setCloseError(true);
    } finally {
      setBusy((b) => ({ ...b, payment: false }));
    }
  };

  const logout = async () => {
    try {
      await api.logout();
    } catch {
      // Session may already be gone.
    }
    onSignedOut();
  };

  const enableSound = () => {
    try {
      const Ctor = window.AudioContext ?? (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
      audioCtx.current = new Ctor();
      setSoundEnabled(true);
    } catch {
      setSoundEnabled(false);
    }
  };

  const visibleOrders = filter === "all" ? orders : orders.filter((o) => o.status === filter);
  const visibleCalls = filter === "all" || filter === "new" ? calls : [];
  // Expired entries are removed by the timer above, so presence means "flashing".
  const isFlashing = (id: string) => id in flashUntil;

  return (
    <>
      <div className="staff">
        <header className="staff-header">
          <StaffHeaderBrand />

          <div className="staff-header__meta">
            <a className="staff-header__qr-link" href={`${API_URL}/admin/cafe-tables/qr`} target="_blank" rel="noopener">
              <span>📷 Masa QR Kodları</span>
            </a>
            <button className="staff-header__qr-link staff-header__logout" type="button" onClick={logout} title={user.email}>
              <span>👤 {user.name} · Çıkış</span>
            </button>
            <div className="live-status">
              <span className="pulse" aria-hidden="true" />
              <span>{orders.length} aktif sipariş</span>
            </div>
          </div>
        </header>

        <div className="staff-controls">
          <button className={`sound-toggle${soundEnabled ? " is-enabled" : ""}`} type="button" onClick={enableSound}>
            <span>🔔 Sesli Bildirimleri Etkinleştir</span>
          </button>

          <div className="filters" role="tablist" aria-label="Durum filtresi">
            {FILTERS.map((f) => (
              <button
                key={f.id}
                className={`${f.className}${filter === f.id ? " is-active" : ""}`}
                type="button"
                onClick={() => setFilter(f.id)}
              >
                <span>{f.label}</span>
              </button>
            ))}
          </div>
        </div>

        <main className="board">
          {visibleCalls.map((call) => (
            <article key={`call_${call.id}`} className={`order is-new${isFlashing(`call_${call.id}`) ? " is-flash" : ""}`}>
              <div className="order__top">
                <h2 className="order__table">Masa {call.table}</h2>
                <span className="order__time">{formatTime(call.createdAt)}</span>
              </div>
              <span className="order__status">{call.type === "bill" ? "Hesap İsteği" : "Garson Çağrısı"}</span>
              {call.reason && (
                <ul className="order__items">
                  <li>
                    <span>{call.reason}</span>
                  </li>
                </ul>
              )}
              <div className="order__actions">
                <button type="button" className="is-primary" disabled={busy[`call_${call.id}`]} onClick={() => resolveCall(call)}>
                  Tamam
                </button>
              </div>
            </article>
          ))}

          {visibleOrders.map((order) => {
            const next = NEXT_ACTION[order.status];
            return (
              <article key={order.id} className={`order is-${order.status}${isFlashing(order.id) ? " is-flash" : ""}`}>
                <div className="order__top">
                  <h2 className="order__table">
                    Masa {order.table}
                    {order.hasNewItems && <span className="order-badge-has-new">Yeni Sipariş</span>}
                  </h2>
                  <span className="order__time">{formatTime(order.createdAt)}</span>
                </div>
                <span className="order__status">{STATUS_LABEL[order.status] ?? order.status}</span>
                <ul className="order__items">
                  {order.items.map((item, index) => (
                    <li key={index} className={item.isNew ? "is-new-item" : undefined}>
                      <span>
                        {item.name}
                        {item.isNew && <span className="order-item-badge-new">YENİ</span>}
                      </span>
                      <span className="order__qty">×{item.qty}</span>
                    </li>
                  ))}
                </ul>
                {order.note && <p className="order__note">{order.note}</p>}
                <div className="order__total">
                  <span className="order__total-label">Toplam</span>
                  <span className="order__total-amount">{formatCents(toCents(order.total))}</span>
                </div>
                <div className="order__actions">
                  {next && (
                    <button type="button" className="is-primary" disabled={busy[order.id]} onClick={() => updateStatus(order, next.status)}>
                      {next.label}
                    </button>
                  )}
                  <button
                    type="button"
                    className={order.status === "delivered" ? "is-primary" : undefined}
                    onClick={() => {
                      setCloseError(false);
                      setClosing(order);
                    }}
                  >
                    Kapat
                  </button>
                </div>
              </article>
            );
          })}

          {visibleOrders.length === 0 && visibleCalls.length === 0 && (
            <div className="empty">
              <div className="empty__icon">☕</div>
              {connectionError && !loaded ? (
                <p className="empty__title">Sunucuya bağlanılamadı.</p>
              ) : (
                <>
                  <p className="empty__title">Henüz Aktif Sipariş Bulunmuyor</p>
                  <p className="empty__desc">Masalardan yeni bir sipariş geldiğinde burada anında görünecektir.</p>
                </>
              )}
            </div>
          )}
        </main>

        {connectionError && loaded && (
          <p className="staff-connection-warning" role="status">
            Sunucuya bağlanılamadı — yeniden deneniyor…
          </p>
        )}
      </div>

      {closing && (
        <div className="payment-modal" aria-modal="true" role="dialog" aria-labelledby="paymentModalTitle">
          <div className="payment-modal__backdrop" onClick={() => setClosing(null)} />
          <div className="payment-modal__box">
            <div className="payment-modal__icon">💳</div>
            <h2 className="payment-modal__title" id="paymentModalTitle">
              Masa Kapatılıyor
            </h2>
            <p className="payment-modal__table">Masa {closing.table}</p>
            <div className="payment-modal__total-block">
              <span className="payment-modal__total-label">Toplam Tutar</span>
              <span className="payment-modal__total-val">{formatCents(toCents(closing.total))}</span>
            </div>
            <p className="payment-modal__desc">Ödeme alındıktan sonra masayı kapatabilirsiniz.</p>
            <div className="payment-modal__actions">
              <button type="button" className="payment-modal__btn payment-modal__btn--cancel" onClick={() => setClosing(null)}>
                İptal
              </button>
              <button
                type="button"
                className="payment-modal__btn payment-modal__btn--confirm"
                disabled={busy.payment}
                onClick={confirmClose}
              >
                {busy.payment ? "Kapatılıyor..." : closeError ? "Hata! Tekrar Dene" : "✓ Ödeme Alındı, Kapat"}
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
