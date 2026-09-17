export default function Loader({ done }: { done: boolean }) {
  return (
    <div className={`loader${done ? " is-done" : ""}`} aria-live="polite">
      <div className="loader__content">
        <div className="loader__emblem">
          <svg className="loader__svg" viewBox="0 0 80 80" fill="none">
            <circle cx="40" cy="40" r="36" stroke="#C99A5B" strokeWidth="1.5" strokeDasharray="4 4" opacity="0.6" />
            <path d="M26 48C26 36 34 26 40 26C46 26 54 36 54 48H26Z" fill="#F7F3EA" opacity="0.9" />
            <path d="M22 48H58V50C58 54.4 54.4 58 50 58H30C25.6 58 22 54.4 22 50V48Z" fill="#C99A5B" />
            <path d="M54 36C58 36 62 39 62 43C62 47 58 50 54 50" stroke="#C99A5B" strokeWidth="2.5" strokeLinecap="round" />
            <path d="M34 22C34 18 38 16 38 12" stroke="#DFD8C8" strokeWidth="1.5" strokeLinecap="round" opacity="0.8" />
            <path d="M42 22C42 18 46 16 46 12" stroke="#DFD8C8" strokeWidth="1.5" strokeLinecap="round" opacity="0.8" />
          </svg>
        </div>
        <h1 className="loader__title">Book &amp; Tea House</h1>
        <p className="loader__subtitle">A sanctuary for book lovers &amp; tea connoisseurs</p>
        <div className="loader__bar">
          <div className="loader__progress" />
        </div>
      </div>
    </div>
  );
}
