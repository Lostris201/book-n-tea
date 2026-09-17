const OPTIONS = [
  { reason: "Masaya Garson İstiyorum", label: "Garson Çağır", icon: "🙋‍♂️", type: "waiter" },
  { reason: "Su & Peçete İsteği", label: "Su & Peçete İsteği", icon: "💧", type: "waiter" },
  { reason: "Hesap / Adisyon İsteği", label: "Hesap / Adisyon Getir", icon: "🧾", type: "bill" },
  { reason: "Masa Temizliği", label: "Masa Temizliği", icon: "🧹", type: "waiter" },
] as const;

type Props = {
  allowWaiter: boolean;
  allowBill: boolean;
  onSelect: (type: "waiter" | "bill", reason: string) => void;
  onClose: () => void;
};

export default function WaiterModal({ allowWaiter, allowBill, onSelect, onClose }: Props) {
  const options = OPTIONS.filter((o) => (o.type === "bill" ? allowBill : allowWaiter));

  return (
    <div className="modal">
      <div className="modal__backdrop" onClick={onClose} />
      <div className="modal__dialog modal__dialog--sm" role="dialog" aria-label="Garson Çağır">
        <button type="button" className="modal__close" onClick={onClose} aria-label="Kapat">
          ✕
        </button>
        <div className="waiter-header">
          <div className="waiter-header__icon">🛎️</div>
          <h3 className="modal__title">Garson Çağır</h3>
          <p className="modal__subtitle">Ekibimize bildirmek istediğiniz talebi seçin:</p>
        </div>

        <div className="waiter-actions">
          {options.map((option) => (
            <button
              key={option.reason}
              type="button"
              className="waiter-option-btn"
              onClick={() => onSelect(option.type, option.reason)}
            >
              <span className="waiter-option-btn__icon">{option.icon}</span>
              <span>{option.label}</span>
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}
