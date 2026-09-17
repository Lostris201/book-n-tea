/** Cart math in kuruş to avoid floating-point drift; prices from the API are TL numbers. */
export const toCents = (tl: number) => Math.round(tl * 100);

/** 9500 → "95 ₺", 9050 → "90,50 ₺" (same look as the legacy menu for whole prices). */
export function formatCents(cents: number): string {
  const tl = cents / 100;
  return Number.isInteger(tl)
    ? `${tl} ₺`
    : `${tl.toLocaleString("tr-TR", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₺`;
}

export const formatTl = (tl: number) => formatCents(toCents(tl));
