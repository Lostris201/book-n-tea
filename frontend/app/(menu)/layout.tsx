import type { Metadata, Viewport } from "next";
import type { ReactNode } from "react";
import { fontVariables } from "../fonts";
import "./menu.css";

export const metadata: Metadata = {
  title: "Book & Tea House — Menü & Sipariş",
  description:
    "Book & Tea House — Minimalist & Lüks QR Kafe Menüsü. Özel çaylar, leziz kahveler ve kütüphane fırınından tatlılar.",
};

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  maximumScale: 1,
  userScalable: false,
  themeColor: "#183327",
};

// Separate root layout: the menu and staff board ship different global stylesheets.
export default function MenuRootLayout({ children }: { children: ReactNode }) {
  return (
    <html lang="tr" style={fontVariables}>
      <body>{children}</body>
    </html>
  );
}
