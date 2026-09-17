import { formatCents } from "@/lib/money";
import type { CartLine } from "@/lib/cart";

type Props = {
  tableLabel: string;
  lines: CartLine[];
  totalCents: number;
  note: string;
  submitting: boolean;
  error: string | null;
  onNoteChange: (note: string) => void;
  onChangeQty: (key: string, delta: number) => void;
  onSubmit: () => void;
  onClose: () => void;
};

export default function CartDrawer({
  tableLabel,
  lines,
  totalCents,
  note,
  submitting,
  error,
  onNoteChange,
  onChangeQty,
  onSubmit,
  onClose,
}: Props) {
  return (
    <div className="drawer">
      <div className="drawer__backdrop" onClick={onClose} />
      <div className="drawer__panel" role="dialog" aria-labelledby="cartDrawerTitle">
        <div className="drawer__handle" />

        <div className="drawer__header">
          <div>
            <h2 className="drawer__title" id="cartDrawerTitle">
              Siparişiniz
            </h2>
            <span className="drawer__table-pill">{tableLabel}</span>
          </div>
          <button type="button" className="drawer__close" onClick={onClose} aria-label="Kapat">
            ✕
          </button>
        </div>

        <div className="drawer__body">
          <ul className="cart-items">
            {lines.length === 0 ? (
              <li className="cart-empty-msg" style={{ textAlign: "center", color: "var(--cream-dim)", padding: "2rem 0" }}>
                Sepetiniz henüz boş.
              </li>
            ) : (
              lines.map((line) => (
                <li className="cart-item" key={line.key}>
                  <div className="cart-item__info">
                    <h4 className="cart-item__title">{line.name}</h4>
                    {line.optionsLabel && <p className="cart-item__opts">{line.optionsLabel}</p>}
                    <div className="cart-item__price">{formatCents(line.unitCents * line.qty)}</div>
                  </div>
                  <div className="cart-item__controls">
                    <button type="button" onClick={() => onChangeQty(line.key, -1)} aria-label="Azalt">
                      −
                    </button>
                    <span>{line.qty}</span>
                    <button type="button" onClick={() => onChangeQty(line.key, 1)} aria-label="Artır">
                      +
                    </button>
                  </div>
                </li>
              ))
            )}
          </ul>

          <div className="drawer__field">
            <label htmlFor="orderNoteInput" className="drawer__field-label">
              <span>Mutfak / Garson Notu</span>
              <span className="drawer__field-opt">(isteğe bağlı)</span>
            </label>
            <textarea
              id="orderNoteInput"
              className="drawer__textarea"
              rows={2}
              maxLength={150}
              placeholder="Örn: Yulaf sütlü olsun, az şekerli servis edilsin..."
              value={note}
              onChange={(e) => onNoteChange(e.target.value)}
            />
          </div>

          <div className="drawer__summary">
            <div className="summary-row">
              <span>Ara Toplam</span>
              <span>{formatCents(totalCents)}</span>
            </div>
            <div className="summary-row summary-row--free">
              <span>Masa Servis Ücreti</span>
              <span>Ücretsiz</span>
            </div>
            <div className="summary-row summary-row--total">
              <span>Genel Toplam</span>
              <strong>{formatCents(totalCents)}</strong>
            </div>
          </div>
        </div>

        <div className="drawer__footer">
          <button className="btn-primary" type="button" onClick={onSubmit} disabled={submitting}>
            <span className="btn-primary__text">{submitting ? "Gönderiliyor..." : "Siparişi Masaya Gönder"}</span>
            <span className="btn-primary__icon">➔</span>
          </button>
          {error && (
            <p className="drawer__error" role="alert">
              {error}
            </p>
          )}
        </div>
      </div>
    </div>
  );
}
