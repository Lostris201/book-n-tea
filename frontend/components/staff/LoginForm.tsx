"use client";

import { type FormEvent, useState } from "react";
import { ApiError, api } from "@/lib/api";
import type { StaffUser } from "@/lib/types";
import StaffHeaderBrand from "./StaffHeaderBrand";

type Props = {
  initialMessage?: string;
  onLoggedIn: (user: StaffUser) => void;
};

export default function LoginForm({ initialMessage, onLoggedIn }: Props) {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(initialMessage ?? null);
  const [submitting, setSubmitting] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setSubmitting(true);
    setError(null);
    try {
      const { user } = await api.login(email.trim(), password);
      if (!["admin", "manager", "staff"].includes(user.role)) {
        setError("Bu işlem için yetkiniz yok.");
        return;
      }
      setPassword("");
      onLoggedIn(user);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Giriş yapılamadı.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="staff">
      <header className="staff-header">
        <StaffHeaderBrand />
      </header>

      <form className="staff-login" onSubmit={submit}>
        <div className="staff-login__icon">🔐</div>
        <h2 className="staff-login__title">Personel Girişi</h2>
        <p className="staff-login__desc">Sipariş panosunu görmek için hesabınızla giriş yapın.</p>

        <label className="staff-login__field">
          <span>E-posta</span>
          <input
            type="email"
            autoComplete="username"
            required
            value={email}
            onChange={(e) => setEmail(e.target.value)}
          />
        </label>

        <label className="staff-login__field">
          <span>Şifre</span>
          <input
            type="password"
            autoComplete="current-password"
            required
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
        </label>

        {error && (
          <p className="staff-login__error" role="alert">
            {error}
          </p>
        )}

        <button className="staff-login__submit" type="submit" disabled={submitting}>
          {submitting ? "Giriş yapılıyor…" : "Giriş Yap"}
        </button>
      </form>
    </div>
  );
}
