"use client";

import { useEffect, useState } from "react";
import { ApiError, api } from "@/lib/api";
import type { StaffUser } from "@/lib/types";
import Board from "./Board";
import LoginForm from "./LoginForm";

type AuthState = { status: "checking" } | { status: "guest"; message?: string } | { status: "user"; user: StaffUser };

export default function StaffApp() {
  const [auth, setAuth] = useState<AuthState>({ status: "checking" });

  useEffect(() => {
    api
      .me()
      .then(({ user }) => setAuth({ status: "user", user }))
      .catch((err) =>
        setAuth({
          status: "guest",
          message: err instanceof ApiError && err.status === 0 ? err.message : undefined,
        }),
      );
  }, []);

  if (auth.status === "checking") {
    return (
      <div className="staff">
        <div className="empty">
          <div className="empty__icon">☕</div>
          <p className="empty__title">Yükleniyor…</p>
        </div>
      </div>
    );
  }

  if (auth.status === "guest") {
    return <LoginForm initialMessage={auth.message} onLoggedIn={(user) => setAuth({ status: "user", user })} />;
  }

  return (
    <Board
      user={auth.user}
      onSignedOut={(message) => setAuth({ status: "guest", message })}
    />
  );
}
