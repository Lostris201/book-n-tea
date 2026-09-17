import type { Metadata } from "next";
import type { ReactNode } from "react";
import { fontVariables } from "../../fonts";
import "./staff.css";

export const metadata: Metadata = {
  title: "Book & Tea House — Kafe Personel Paneli",
  description: "Book & Tea House — Canlı Mutfak & Kafe Personel Paneli",
  robots: { index: false, follow: false },
};

export default function StaffRootLayout({ children }: { children: ReactNode }) {
  return (
    <html lang="tr" style={fontVariables}>
      <body>{children}</body>
    </html>
  );
}
