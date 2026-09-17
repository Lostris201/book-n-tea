"use client";

import { useCallback, useEffect, useState } from "react";

export type CartLine = {
  key: string; // productId + chosen option ids
  productId: string;
  name: string;
  optionIds: string[];
  optionsLabel: string;
  unitCents: number; // display only — the server prices the order
  qty: number;
};

const MAX_QTY = 50;

export function lineKey(productId: string, optionIds: string[]) {
  return [productId, ...[...optionIds].sort()].join("|");
}

/** Cart per table token in sessionStorage (per device, non-sensitive). */
export function useCart(tableToken: string | null) {
  const storageKey = tableToken ? `bnt_cart_${tableToken}` : null;
  const [lines, setLines] = useState<CartLine[]>([]);
  const [loadedKey, setLoadedKey] = useState<string | null>(null);

  // Load once per token.
  useEffect(() => {
    if (!storageKey) return;
    try {
      const raw = sessionStorage.getItem(storageKey);
      const parsed = raw ? (JSON.parse(raw) as CartLine[]) : [];
      // eslint-disable-next-line react-hooks/set-state-in-effect -- hydrating from browser storage
      setLines(Array.isArray(parsed) ? parsed : []);
    } catch {
      setLines([]);
    }
    setLoadedKey(storageKey);
  }, [storageKey]);

  // Persist after the initial load.
  useEffect(() => {
    if (!storageKey || loadedKey !== storageKey) return;
    try {
      sessionStorage.setItem(storageKey, JSON.stringify(lines));
    } catch {
      // Private mode / quota: the cart still works in memory.
    }
  }, [lines, storageKey, loadedKey]);

  const add = useCallback((line: Omit<CartLine, "key">) => {
    const key = lineKey(line.productId, line.optionIds);
    setLines((current) => {
      const existing = current.find((l) => l.key === key);
      if (existing) {
        return current.map((l) => (l.key === key ? { ...l, qty: Math.min(MAX_QTY, l.qty + line.qty) } : l));
      }
      return [...current, { ...line, key, qty: Math.min(MAX_QTY, line.qty) }];
    });
  }, []);

  const changeQty = useCallback((key: string, delta: number) => {
    setLines((current) =>
      current
        .map((l) => (l.key === key ? { ...l, qty: Math.min(MAX_QTY, l.qty + delta) } : l))
        .filter((l) => l.qty > 0),
    );
  }, []);

  /** Card +/− buttons act on the first cart line of that product (same as the legacy menu). */
  const changeProductQty = useCallback((productId: string, delta: number) => {
    setLines((current) => {
      const first = current.find((l) => l.productId === productId);
      if (!first) return current;
      return current
        .map((l) => (l.key === first.key ? { ...l, qty: Math.min(MAX_QTY, l.qty + delta) } : l))
        .filter((l) => l.qty > 0);
    });
  }, []);

  const clear = useCallback(() => setLines([]), []);

  const count = lines.reduce((sum, l) => sum + l.qty, 0);
  const totalCents = lines.reduce((sum, l) => sum + l.unitCents * l.qty, 0);
  const qtyForProduct = (productId: string) =>
    lines.filter((l) => l.productId === productId).reduce((sum, l) => sum + l.qty, 0);

  return { lines, add, changeQty, changeProductQty, clear, count, totalCents, qtyForProduct };
}
