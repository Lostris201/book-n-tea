type Props = {
  tableLabel: string;
  showWaiterButton: boolean;
  onCallWaiter: () => void;
};

export default function Hero({ tableLabel, showWaiterButton, onCallWaiter }: Props) {
  return (
    <>
      <div className="quote-bar">
        <span className="quote-bar__icon">📖</span>
        <span className="quote-bar__text">&quot;Bir fincan çay, iyi bir kitap ve sessiz bir köşe...&quot;</span>
      </div>

      <header className="hero">
        <div className="hero__bg-glow" />

        {/* The table comes from the QR code and can't be changed here. */}
        <div className="table-badge">
          <span className="table-badge__pin">📍</span>
          <span className="table-badge__label">{tableLabel}</span>
        </div>

        <div className="hero__logo-wrapper">
          <svg className="hero__logo-svg" viewBox="0 0 100 100" fill="none">
            <circle cx="50" cy="50" r="46" stroke="#C99A5B" strokeWidth="1.5" strokeDasharray="3 3" opacity="0.4" />
            <circle cx="50" cy="50" r="41" stroke="#F7F3EA" strokeWidth="0.75" opacity="0.25" />
            <path
              d="M28 62C34 58 43 57 50 61C57 57 66 58 72 62V38C66 34 57 33 50 37C43 33 34 34 28 38V62Z"
              stroke="#F7F3EA"
              strokeWidth="1.8"
              strokeLinejoin="round"
            />
            <path d="M50 37V61" stroke="#C99A5B" strokeWidth="1.5" />
            <path d="M38 48H62C62 55 56.6 60 50 60C43.4 60 38 55 38 48Z" fill="#204232" stroke="#C99A5B" strokeWidth="1.5" />
            <path d="M44 32C44 28 47 27 47 23" stroke="#C99A5B" strokeWidth="1.5" strokeLinecap="round" />
            <path d="M53 32C53 28 56 27 56 23" stroke="#C99A5B" strokeWidth="1.5" strokeLinecap="round" />
          </svg>
        </div>

        <h1 className="hero__title">Book &amp; Tea House</h1>
        <p className="hero__slogan">A sanctuary for book lovers &amp; tea connoisseurs</p>

        {showWaiterButton && (
          <div className="quick-actions">
            <button className="quick-btn" type="button" onClick={onCallWaiter}>
              <span className="quick-btn__icon">🛎️</span>
              <span className="quick-btn__text">Garson Çağır</span>
            </button>
          </div>
        )}
      </header>
    </>
  );
}
