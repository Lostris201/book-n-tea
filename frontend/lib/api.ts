/**
 * Laravel API client. The base URL is public on purpose (NEXT_PUBLIC_API_URL) — nothing secret lives here.
 *
 * Auth uses Sanctum SPA cookies: requests send credentials, and state-changing requests carry the
 * X-XSRF-TOKEN header read from the XSRF-TOKEN cookie (fetched from /sanctum/csrf-cookie when missing).
 */
import type { Menu, Order, ResolvedTable, StaffUser, WaiterCall } from "./types";

export const API_URL = (process.env.NEXT_PUBLIC_API_URL ?? "").replace(/\/+$/, "");

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
  ) {
    super(message);
  }
}

function readCookie(name: string): string | null {
  if (typeof document === "undefined") return null;
  const match = document.cookie.split("; ").find((row) => row.startsWith(`${name}=`));
  return match ? decodeURIComponent(match.slice(name.length + 1)) : null;
}

async function ensureCsrfCookie(force = false): Promise<void> {
  if (!force && readCookie("XSRF-TOKEN")) return;
  await fetch(`${API_URL}/sanctum/csrf-cookie`, { credentials: "include" });
}

type RequestOptions = { method?: string; body?: unknown; signal?: AbortSignal };

async function request<T>(path: string, { method = "GET", body, signal }: RequestOptions = {}, retried = false): Promise<T> {
  const mutating = method !== "GET" && method !== "HEAD";
  if (mutating) await ensureCsrfCookie();

  const headers: Record<string, string> = { Accept: "application/json" };
  if (body !== undefined) headers["Content-Type"] = "application/json";
  const xsrf = readCookie("XSRF-TOKEN");
  if (mutating && xsrf) headers["X-XSRF-TOKEN"] = xsrf;

  let res: Response;
  try {
    res = await fetch(`${API_URL}${path}`, {
      method,
      headers,
      credentials: "include",
      body: body === undefined ? undefined : JSON.stringify(body),
      signal,
    });
  } catch (err) {
    if (err instanceof DOMException && err.name === "AbortError") throw err;
    throw new ApiError(0, "Sunucuya bağlanılamadı. İnternet bağlantınızı kontrol edin.");
  }

  // Expired CSRF token / session: refresh the cookie and retry once.
  if (res.status === 419 && mutating && !retried) {
    await ensureCsrfCookie(true);
    return request<T>(path, { method, body, signal }, true);
  }

  const data = await res.json().catch(() => null);

  if (!res.ok) {
    const message = data && typeof data.error === "string" ? data.error : "Bir hata oluştu. Lütfen tekrar deneyin.";
    throw new ApiError(res.status, message);
  }

  return data as T;
}

export const api = {
  menu: (signal?: AbortSignal) => request<Menu>("/api/menu", { signal }),

  resolveTable: (token: string) => request<ResolvedTable>(`/api/tables/resolve?t=${encodeURIComponent(token)}`),

  placeOrder: (payload: {
    table_token: string;
    items: { product_id: string; qty: number; option_ids: string[] }[];
    note: string;
  }) => request<Order>("/api/orders", { method: "POST", body: payload }),

  callWaiter: (payload: { table_token: string; type: "waiter" | "bill"; reason: string }) =>
    request<WaiterCall>("/api/waiter-calls", { method: "POST", body: payload }),

  // Staff
  me: () => request<{ user: StaffUser }>("/api/auth/me"),
  login: (email: string, password: string) =>
    request<{ user: StaffUser }>("/api/auth/login", { method: "POST", body: { email, password } }),
  logout: () => request<{ success: boolean }>("/api/auth/logout", { method: "POST" }),
  orders: () => request<Order[]>("/api/orders"),
  updateOrderStatus: (id: string, status: string) =>
    request<Order>(`/api/orders/${encodeURIComponent(id)}`, { method: "PATCH", body: { status } }),
  waiterCalls: () => request<WaiterCall[]>("/api/waiter-calls"),
  resolveWaiterCall: (id: number) => request<WaiterCall>(`/api/waiter-calls/${id}`, { method: "PATCH" }),
};
