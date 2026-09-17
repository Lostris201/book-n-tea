import { formatCents, toCents } from "@/lib/money";
import type { Order } from "@/lib/types";

export default function ReceiptModal({ order, onClose }: { order: Order; onClose: () => void }) {
  return (
    <div className="modal">
      <div className="modal__backdrop" />
      <div className="modal__dialog modal__dialog--receipt" role="dialog" aria-label="Siparişiniz Alındı">
        <div className="receipt">
          <div className="receipt__badge">✓</div>
          <h3 className="receipt__title">Siparişiniz Alındı</h3>
          <p className="receipt__subtitle">Mutfak ekibimiz siparişinizi hazırlamaya başladı.</p>

          <div className="receipt__meta">
            <div className="receipt__row">
              <span>Sipariş No:</span>
              <strong>#{order.id}</strong>
            </div>
            <div className="receipt__row">
              <span>Masa:</span>
              <strong>Masa {order.table}</strong>
            </div>
            <div className="receipt__row">
              <span>Tahmini Süre:</span>
              <strong className="receipt__time">~8 - 12 Dakika</strong>
            </div>
            <div className="receipt__row">
              <span>Durum:</span>
              <span className="receipt__status-tag">Mutfakta Hazırlanıyor ⏳</span>
            </div>
          </div>

          <div className="receipt__items">
            {order.items.map((item, index) => (
              <div className="receipt-item-line" key={index}>
                <span>
                  {item.qty}x {item.name}
                </span>
                <strong>{formatCents(toCents(item.price) * item.qty)}</strong>
              </div>
            ))}
          </div>

          <div className="receipt__total">
            <span>Toplam Ödenek:</span>
            <strong>{formatCents(toCents(order.total))}</strong>
          </div>

          <button type="button" className="btn-primary" onClick={onClose}>
            Ana Menüye Dön
          </button>
        </div>
      </div>
    </div>
  );
}
