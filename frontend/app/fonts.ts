import { Cormorant_Garamond, Inter } from "next/font/google";
import type { CSSProperties } from "react";

// Self-hosted at build time (no request to Google from customers' phones). latin-ext covers Turkish.
const heading = Cormorant_Garamond({
  subsets: ["latin", "latin-ext"],
  weight: ["500", "600", "700"],
  style: ["normal", "italic"],
  display: "swap",
});

const body = Inter({
  subsets: ["latin", "latin-ext"],
  weight: ["300", "400", "500", "600", "700"],
  display: "swap",
});

/** The ported stylesheets read --font-heading / --font-body; point them at the self-hosted fonts. */
export const fontVariables = {
  "--font-heading": `${heading.style.fontFamily}, Georgia, serif`,
  "--font-body": `${body.style.fontFamily}, -apple-system, BlinkMacSystemFont, system-ui, sans-serif`,
} as CSSProperties;
